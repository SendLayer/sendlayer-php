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
            throw $this->mapClientException($e);
        } catch (ServerException $e) {
            throw $this->mapServerException($e);
        } catch (ConnectException $e) {
            throw new SendLayerException('Connection error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new SendLayerException('Unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * Map a client exception (4xx errors) to the appropriate SendLayer exception
     *
     * @param ClientException $e
     * @return SendLayerException
     */
    private function mapClientException(ClientException $e): SendLayerException
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $responseData = $this->parseErrorResponse($e->getResponse());

        switch ($statusCode) {
            case 401:
                $exception = new SendLayerAuthenticationException(
                    $this->extractErrorMessage($responseData, 'Invalid API key')
                );
                break;
            case 400:
                $exception = new SendLayerValidationException(
                    $this->extractErrorMessage($responseData, 'Invalid request parameters')
                );
                break;
            case 404:
                $exception = new SendLayerNotFoundException(
                    $this->extractErrorMessage($responseData, 'Resource not found')
                );
                break;
            case 422:
                $exception = new SendLayerValidationException(
                    $this->extractErrorMessage($responseData, 'Unprocessable Entity')
                );
                break;
            case 429:
                $exception = new SendLayerRateLimitException(
                    $this->extractErrorMessage($responseData, 'Rate limit exceeded')
                );
                break;
            default:
                $exception = new SendLayerAPIException(
                    $this->extractErrorMessage($responseData, 'API request failed'),
                    $statusCode,
                    $responseData
                );
                break;
        }

        $exception->errors = $this->extractErrors($responseData);

        return $exception;
    }

    /**
     * Map a server exception (5xx errors) to the appropriate SendLayer exception
     *
     * @param ServerException $e
     * @return SendLayerException
     */
    private function mapServerException(ServerException $e): SendLayerException
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $responseData = $this->parseErrorResponse($e->getResponse());

        if ($statusCode === 500) {
            $exception = new SendLayerInternalServerException(
                $this->extractErrorMessage($responseData, 'Internal server error')
            );
        } else {
            $exception = new SendLayerAPIException(
                $this->extractErrorMessage($responseData, 'Server error'),
                $statusCode,
                $responseData
            );
        }

        $exception->errors = $this->extractErrors($responseData);

        return $exception;
    }


    /**
     * Normalize the SendLayer "Errors" array from a decoded response body.
     *
     * SendLayer returns errors as: { "Errors": [ { "Code": 14, "Message": "..." } ] }
     *
     * @param array $responseData
     * @return array<int, array<string, mixed>> Empty array when absent or malformed
     */
    private function extractErrors(array $responseData): array
    {
        if (!empty($responseData['Errors']) && is_array($responseData['Errors'])) {
            return array_values(array_filter($responseData['Errors'], 'is_array'));
        }

        return [];
    }

    /**
     * Build a human-readable message from the "Errors" array.
     *
     * Returns the real API message(s) verbatim, joined by "; " when several are present.
     * Falls back to the singular "Error" key (reason-phrase path) and then $default.
     *
     * @param array $responseData
     * @param string $default
     * @return string
     */
    private function extractErrorMessage(array $responseData, string $default): string
    {
        $parts = [];
        foreach ($this->extractErrors($responseData) as $error) {
            if (isset($error['Message'])) {
                $parts[] = (string) $error['Message'];
            }
        }

        if (!empty($parts)) {
            return implode('; ', $parts);
        }

        return $responseData['Error'] ?? $default;
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