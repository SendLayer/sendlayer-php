<?php

namespace SendLayer\Exceptions;

/**
 * Exception raised for validation errors
 */
class SendLayerValidationException extends SendLayerException
{
    public function __construct(string $message = "Validation error", int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 