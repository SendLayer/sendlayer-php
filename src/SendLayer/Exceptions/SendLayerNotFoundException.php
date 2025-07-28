<?php

namespace SendLayer\Exceptions;

/**
 * Exception raised for not found errors
 */
class SendLayerNotFoundException extends SendLayerException
{
    public function __construct(string $message = "Resource not found", int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 