<?php

namespace SendLayer\Exceptions;

/**
 * Base exception for SendLayer SDK
 */
class SendLayerException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 