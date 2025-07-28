<?php

namespace SendLayer\Exceptions;

/**
 * Exception raised for rate limit errors
 */
class SendLayerRateLimitException extends SendLayerException
{
    public function __construct(string $message = "Rate limit exceeded", int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 