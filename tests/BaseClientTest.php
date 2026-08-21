<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerException;
use SendLayer\Exceptions\SendLayerValidationException;
use SendLayer\Exceptions\SendLayerNotFoundException;
use SendLayer\Exceptions\SendLayerInternalServerException;
use SendLayer\Exceptions\SendLayerAPIException;

class BaseClientTest extends TestCase
{
    /**
     * Build a BaseClient whose Guzzle client uses a mocked response queue,
     * exercising the real makeRequest() -> parseErrorResponse() -> mapper path.
     */
    private function createClientWithResponse(Response $response): BaseClient
    {
        $mock = new MockHandler([$response]);
        $stack = HandlerStack::create($mock);

        return new BaseClient('test_api_key', ['guzzle' => ['handler' => $stack]]);
    }

    public function testClientErrorParsesErrorsArray()
    {
        $body = json_encode([
            'Errors' => [
                ['Code' => 14, 'Message' => 'Recipient email is suppressed'],
            ],
        ]);
        $client = $this->createClientWithResponse(new Response(400, [], $body));

        try {
            $client->makeRequest('POST', 'email');
            $this->fail('Expected SendLayerValidationException was not thrown.');
        } catch (SendLayerValidationException $e) {
            // Real API message surfaced verbatim (no code prefix).
            $this->assertSame('Recipient email is suppressed', $e->getMessage());
            // Numeric API code preserved on the structured errors array.
            $this->assertSame(14, $e->errors[0]['Code']);
            $this->assertSame('Recipient email is suppressed', $e->errors[0]['Message']);
            // getCode() remains HTTP-status-based (unchanged behaviour).
            $this->assertSame(400, $e->getCode());
        }
    }

    public function testMultipleErrorsAreJoined()
    {
        $body = json_encode([
            'Errors' => [
                ['Code' => 1, 'Message' => 'First problem'],
                ['Code' => 2, 'Message' => 'Second problem'],
            ],
        ]);
        $client = $this->createClientWithResponse(new Response(404, [], $body));

        try {
            $client->makeRequest('GET', 'missing');
            $this->fail('Expected SendLayerNotFoundException was not thrown.');
        } catch (SendLayerNotFoundException $e) {
            $this->assertSame('First problem; Second problem', $e->getMessage());
            $this->assertCount(2, $e->errors);
            $this->assertSame([1, 2], array_column($e->errors, 'Code'));
        }
    }

    public function testServerErrorParsesErrorsArray()
    {
        $body = json_encode([
            'Errors' => [
                ['Code' => 30, 'Message' => 'Something exploded'],
            ],
        ]);
        $client = $this->createClientWithResponse(new Response(500, [], $body));

        try {
            $client->makeRequest('POST', 'email');
            $this->fail('Expected SendLayerInternalServerException was not thrown.');
        } catch (SendLayerInternalServerException $e) {
            $this->assertSame('Something exploded', $e->getMessage());
            $this->assertSame(30, $e->errors[0]['Code']);
        }
    }

    public function testUnmappedClientStatusUsesApiException()
    {
        $body = json_encode([
            'Errors' => [
                ['Code' => 9, 'Message' => 'Teapot'],
            ],
        ]);
        $client = $this->createClientWithResponse(new Response(418, [], $body));

        try {
            $client->makeRequest('POST', 'email');
            $this->fail('Expected SendLayerAPIException was not thrown.');
        } catch (SendLayerAPIException $e) {
            $this->assertStringContainsString('Teapot', $e->getMessage());
            $this->assertSame(418, $e->statusCode);
            // Full body retained on the APIException.
            $this->assertSame(9, $e->response['Errors'][0]['Code']);
            $this->assertSame(9, $e->errors[0]['Code']);
        }
    }

    public function testNonJsonBodyFallsBackToReasonPhrase()
    {
        $client = $this->createClientWithResponse(new Response(400, [], 'not json at all'));

        try {
            $client->makeRequest('POST', 'email');
            $this->fail('Expected SendLayerValidationException was not thrown.');
        } catch (SendLayerValidationException $e) {
            // No parseable Errors array -> reason-phrase fallback, empty structured errors.
            $this->assertSame('Bad Request', $e->getMessage());
            $this->assertSame([], $e->errors);
        }
    }
}
