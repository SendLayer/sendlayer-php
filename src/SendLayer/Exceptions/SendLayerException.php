<?php

namespace SendLayer\Exceptions;

/**
 * Base exception for SendLayer SDK
 */
class SendLayerException extends \Exception
{
    /**
     * Raw SendLayer "Errors" entries from the API response (each with Code/Message).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $errors = [];

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 