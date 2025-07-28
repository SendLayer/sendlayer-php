<?php

namespace SendLayer\Exceptions;

/**
 * Exception raised for API errors
 */
class SendLayerAPIException extends SendLayerException
{
    public string $message;
    public int $statusCode;
    public array $response;

    public function __construct(string $message, int $statusCode, array $response = [])
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->response = $response;
        
        parent::__construct("API Error {$statusCode}: {$message}", $statusCode);
    }
} 