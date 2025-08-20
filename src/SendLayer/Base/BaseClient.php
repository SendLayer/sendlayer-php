<?php

namespace SendLayer\Base;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use SendLayer\Exceptions\SendLayerException;
use SendLayer\Exceptions\SendLayerAPIException;
use SendLayer\Exceptions\SendLayerAuthenticationException;
use SendLayer\Exceptions\SendLayerValidationException;
use SendLayer\Exceptions\SendLayerNotFoundException;
use SendLayer\Exceptions\SendLayerRateLimitException;
use SendLayer\Exceptions\SendLayerInternalServerException;

/**
 * Base client for SendLayer API interactions
 */
class BaseClient
{
    private Client $httpClient;
    public int $attachmentUrlTimeout;
    private string $apiKey;
    private string $baseUrl = 'https://console.sendlayer.com/api/v1/';

    /**
     * Initialize the base client with API key and optional configuration
     *
     * @param string $apiKey Your SendLayer API key
     * @param array $config Optional configuration array
     */
    public function __construct(string $apiKey, array $config = [])
    {
        $this->apiKey = $apiKey;
        $this->attachmentUrlTimeout = $config['attachmentURLTimeout'] ?? 30000;

        $clientConfig = [
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ],
            'timeout' => $config['timeout'] ?? 30,
        ];

        // Merge any additional Guzzle configuration
        if (isset($config['guzzle'])) {
            $clientConfig = array_merge($clientConfig, $config['guzzle']);
        }

        $this->httpClient = new Client($clientConfig);
    }

    /**
     * Make an HTTP request to the SendLayer API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws SendLayerException
     */
    public function makeRequest(string $method, string $endpoint, array $options = []): array
    {
        
        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SendLayerException('Invalid JSON response from API');
            }
            
            return $data ?? [];
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (ServerException $e) {
            $this->handleServerException($e);
        } catch (ConnectException $e) {
            throw new SendLayerException('Connection error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new SendLayerException('Unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * Handle client exceptions (4xx errors)
     *
     * @param ClientException $e
     * @throws SendLayerException
     */
    private function handleClientException(ClientException $e): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $responseData = $this->parseErrorResponse($e->getResponse());

        switch ($statusCode) {
            case 401:
                throw new SendLayerAuthenticationException($responseData['Error'] ?? 'Invalid API key');
            case 400:
                throw new SendLayerValidationException($responseData['Error'] ?? 'Invalid request parameters');
            case 404:
                throw new SendLayerNotFoundException($responseData['Error'] ?? 'Resource not found');
            case 422:
                throw new SendLayerValidationException($responseData['Error'] ?? 'Unprocessable Entity');
            case 429:
                throw new SendLayerRateLimitException($responseData['Error'] ?? 'Rate limit exceeded');
            default:
                throw new SendLayerAPIException(
                    $responseData['Error'] ?? 'API request failed',
                    $statusCode,
                    $responseData
                );
        }
    }

    /**
     * Handle server exceptions (5xx errors)
     *
     * @param ServerException $e
     * @throws SendLayerException
     */
    private function handleServerException(ServerException $e): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $responseData = $this->parseErrorResponse($e->getResponse());

        if ($statusCode === 500) {
            throw new SendLayerInternalServerException($responseData['Error'] ?? 'Internal server error');
        }

        throw new SendLayerAPIException(
            $responseData['Error'] ?? 'Server error',
            $statusCode,
            $responseData
        );
    }


    /**
     * Parse error response from API
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    private function parseErrorResponse($response): array
    {
        try {
            $contents = $response->getBody()->getContents();
            $data = json_decode($contents, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data ?? [];
            }
        } catch (\Exception $e) {
            // Ignore parsing errors
        }

        return ['Error' => $response->getReasonPhrase() ?? 'Unknown error'];
    }
} 