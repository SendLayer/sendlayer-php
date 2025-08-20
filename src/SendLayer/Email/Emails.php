<?php

namespace SendLayer\Email;

use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerException;
use SendLayer\Exceptions\SendLayerValidationException;

/**
 * Client for sending emails through SendLayer
 */
class Emails
{
    private BaseClient $client;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }

    /**
     * Validate email address format
     *
     * @param string $email
     * @return bool
     */
    private function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Read a file and encode it in base64
     *
     * @param string $filePath
     * @return string
     * @throws SendLayerException
     */
    private function readAttachment(string $filePath): string
    {
        // Check if the path is a URL first
        if (filter_var($filePath, FILTER_VALIDATE_URL)) {
            return $this->readRemoteAttachment($filePath);
        }

        return $this->readLocalAttachment($filePath);
    }

    /**
     * Read a remote file from URL
     *
     * @param string $url
     * @return string
     * @throws SendLayerException
     */
    private function readRemoteAttachment(string $url): string
    {
        try {
            $timeout = $this->client->attachmentUrlTimeout / 1000; // Convert to seconds
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'user_agent' => 'SendLayer-PHP-SDK/1.0.0'
                ]
            ]);

            $content = file_get_contents($url, false, $context);
            
            if ($content === false) {
                throw new SendLayerException("Error fetching remote file: {$url}");
            }

            return base64_encode($content);
        } catch (\Exception $e) {
            throw new SendLayerException("Error reading remote attachment: " . $e->getMessage());
        }
    }

    /**
     * Read a local file
     *
     * @param string $filePath
     * @return string
     * @throws SendLayerException
     */
    private function readLocalAttachment(string $filePath): string
    {
        // Try different path variations
        $paths = [
            $filePath,
            realpath($filePath),
            getcwd() . DIRECTORY_SEPARATOR . $filePath
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_file($path) && is_readable($path)) {
                try {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        throw new SendLayerException("Could not read file: {$path}");
                    }
                    return base64_encode($content);
                } catch (\Exception $e) {
                    throw new SendLayerException("Error reading attachment: " . $e->getMessage());
                }
            }
        }

        throw new SendLayerException("Attachment file not found: {$filePath}");
    }

    /**
     * Validate attachment format
     *
     * @param array $attachment
     * @throws SendLayerValidationException
     */
    private function validateAttachment(array $attachment): void
    {
        if (empty($attachment['path'])) {
            throw new SendLayerValidationException("Attachment path is required");
        }
        if (empty($attachment['type'])) {
            throw new SendLayerValidationException("Attachment type is required");
        }
    }

    /**
     * Validate and normalize recipient
     *
     * @param string|array $recipient
     * @param string $recipientType
     * @return array
     * @throws SendLayerValidationException
     */
    private function validateRecipient($recipient, string $recipientType = "recipient"): array
    {
        if (is_string($recipient)) {
            if (!$this->validateEmail($recipient)) {
                throw new SendLayerValidationException("Invalid {$recipientType} email address: {$recipient}");
            }
            return ['email' => $recipient];
        }

        if (is_array($recipient)) {
            if (empty($recipient['email']) || !$this->validateEmail($recipient['email'])) {
                throw new SendLayerValidationException("Invalid {$recipientType} email address: " . ($recipient['email'] ?? 'missing'));
            }
            return $recipient;
        }

        throw new SendLayerValidationException("Invalid {$recipientType} format");
    }

    /**
     * Send an email through SendLayer
     *
     * @param string|array $from
     * @param string|array|array[] $to
     * @param string $subject
     * @param string|null $text
     * @param string|null $html
     * @param string|array|array[]|null $cc
     * @param string|array|array[]|null $bcc
     * @param string|array|array[]|null $replyTo
     * @param array[]|null $attachments
     * @param array|null $headers
     * @param string[]|null $tags
     * @return array
     * @throws SendLayerException
     */
    public function send(
        $from,
        $to,
        string $subject,
        ?string $text = null,
        ?string $html = null,
        $cc = null,
        $bcc = null,
        $replyTo = null,
        ?array $attachments = null,
        ?array $headers = null,
        ?array $tags = null
    ): array {
        if (empty($text) && empty($html)) {
            throw new SendLayerValidationException("Either 'text' or 'html' content must be provided.");
        }

        $fromDetails = $this->validateRecipient($from, "sender");
        $toList = is_array($to) && !isset($to['email']) ? $to : [$to];
        $toList = array_map(fn($r) => $this->validateRecipient($r, "recipient"), $toList);

        $payload = [
            'From' => $fromDetails,
            'To' => $toList,
            'Subject' => $subject,
            'ContentType' => !empty($html) ? 'HTML' : 'Text',
        ];

        if (!empty($html)) {
            $payload['HTMLContent'] = $html;
        } else {
            $payload['PlainContent'] = $text;
        }

        if ($cc !== null) {
            $ccList = is_array($cc) && !isset($cc['email']) ? $cc : [$cc];
            $payload['CC'] = array_map(fn($r) => $this->validateRecipient($r, "cc"), $ccList);
        }

        if ($bcc !== null) {
            $bccList = is_array($bcc) && !isset($bcc['email']) ? $bcc : [$bcc];
            $payload['BCC'] = array_map(fn($r) => $this->validateRecipient($r, "bcc"), $bccList);
        }

        if ($replyTo !== null) {
            $replyToList = is_array($replyTo) && !isset($replyTo['email']) ? $replyTo : [$replyTo];
            $payload['ReplyTo'] = array_map(fn($r) => $this->validateRecipient($r, "reply_to"), $replyToList);
        }

        if ($attachments !== null) {
            $payload['Attachments'] = [];
            foreach ($attachments as $attachment) {
                $this->validateAttachment($attachment);
                $encodedContent = $this->readAttachment($attachment['path']);
                
                $payload['Attachments'][] = [
                    'Content' => $encodedContent,
                    'Type' => $attachment['type'],
                    'Filename' => basename($attachment['path']),
                    'Disposition' => 'attachment',
                    'ContentId' => crc32($attachment['path']) // Using a unique identifier
                ];
            }
        }

        if ($headers !== null) {
            $payload['Headers'] = $headers;
        }

        if ($tags !== null) {
            if (!is_array($tags) || !array_reduce($tags, fn($carry, $tag) => $carry && is_string($tag), true)) {
                throw new SendLayerValidationException("Tags must be a list of strings.");
            }
            $payload['Tags'] = $tags;
        }

        return $this->client->makeRequest('POST', 'email', ['json' => $payload]);
    }
} 