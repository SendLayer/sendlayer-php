<?php

namespace SendLayer\Webhooks;

use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerValidationException;

/**
 * Client for managing webhooks in SendLayer
 */
class Webhooks
{
    private BaseClient $client;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }

    /**
     * Validate URL format
     *
     * @param string $url
     * @return bool
     */
    private function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Create a new webhook
     *
     * @param string $url Webhook URL
     * @param string $event Event type
     * @return array
     * @throws SendLayerValidationException
     */
    public function create(string $url, string $event): array
    {
        if (!$this->validateUrl($url)) {
            throw new SendLayerValidationException("Error: Invalid webhook URL - {$url}");
        }

        $eventOptions = ['bounce', 'click', 'open', 'unsubscribe', 'complaint', 'delivery'];

        // Validate event name
        if (!in_array($event, $eventOptions)) {
            throw new SendLayerValidationException("Error: '{$event}' is not a valid event name. Supported events include " . implode(', ', $eventOptions));
        }

        $payload = [
            'WebhookURL' => $url,
            'Event' => $event
        ];

        return $this->client->makeRequest('POST', 'webhooks', ['json' => $payload]);
    }

    /**
     * Get all webhooks
     *
     * @return array
     */
    public function get(): array
    {
        return $this->client->makeRequest('GET', 'webhooks');
    }

    /**
     * Delete a webhook by ID
     *
     * @param int $webhookId
     * @return array
     * @throws SendLayerValidationException
     */
    public function delete(int $webhookId): array
    {
        // Validate webhook_id
        if ($webhookId <= 0) {
            throw new SendLayerValidationException("WebhookID must be greater than 0");
        }

        return $this->client->makeRequest('DELETE', "webhooks/{$webhookId}");
    }
} 