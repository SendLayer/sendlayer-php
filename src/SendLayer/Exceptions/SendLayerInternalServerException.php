<?php

namespace SendLayer\Exceptions;

/**
 * Exception raised for internal server errors
 */
class SendLayerInternalServerException extends SendLayerException
{
    public function __construct(string $message = "Internal server error", int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 