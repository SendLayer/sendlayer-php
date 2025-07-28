<?php

namespace SendLayer\Exceptions;

/**
 * Exception raised for authentication errors
 */
class SendLayerAuthenticationException extends SendLayerException
{
    public function __construct(string $message = "Invalid API key", int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 