<?php

namespace SendLayer\Events;

use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerValidationException;

/**
 * Client for retrieving email events from SendLayer
 */
class Events
{
    private BaseClient $client;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get email events from SendLayer
     *
     * @param \DateTime|null $startDate Start date for filtering
     * @param \DateTime|null $endDate End date for filtering
     * @param string|null $event Event type filter
     * @param string|null $messageId Specific message ID to filter
     * @param int|null $startFrom Starting index
     * @param int|null $retrieveCount Number of records to retrieve
     * @return array
     * @throws SendLayerValidationException
     */
    public function get(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $event = null,
        ?string $messageId = null,
        ?int $startFrom = null,
        ?int $retrieveCount = null
    ): array {
        $eventOptions = ['accepted', 'rejected', 'delivered', 'opened', 'clicked', 'unsubscribed', 'complained', 'failed'];
        $params = [];

        // Validate date range
        if ($startDate && $endDate && $endDate <= $startDate) {
            throw new SendLayerValidationException("Error: Invalid date range - End date must be after start date");
        }

        if ($startDate) {
            $params['StartDate'] = $startDate->getTimestamp();
        }
        if ($endDate) {
            $params['EndDate'] = $endDate->getTimestamp();
        }

        if ($event) {
            // Validate event name
            if (!in_array($event, $eventOptions)) {
                throw new SendLayerValidationException("Error: Invalid event name - '{$event}' is not a valid event name");
            }
            $params['Event'] = $event;
        }

        if ($messageId) {
            $params['MessageID'] = $messageId;
        }
        if ($startFrom !== null) {
            $params['StartFrom'] = $startFrom;
        }
        if ($retrieveCount !== null) {
            if ($retrieveCount <= 0 || $retrieveCount > 100) {
                throw new SendLayerValidationException("Error: Invalid retrieve count - must be between 1 and 100");
            }
            $params['RetrieveCount'] = $retrieveCount;
        }

        $response = $this->client->makeRequest('GET', 'events', ['query' => $params]);
        $events = $response['Events'] ?? [];
        $totalRecords = $response['TotalRecords'] ?? 0;

        return [
            'totalRecords' => $totalRecords,
            'events' => $events
        ];
    }
} 