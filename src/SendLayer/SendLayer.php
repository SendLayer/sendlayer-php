<?php

namespace SendLayer;

use SendLayer\Base\BaseClient;
use SendLayer\Email\Emails;
use SendLayer\Webhooks\Webhooks;
use SendLayer\Events\Events;

/**
 * Main SendLayer SDK class
 */
class SendLayer
{
    public Emails $Emails;
    public Webhooks $Webhooks;
    public Events $Events;
    private BaseClient $client;

    /**
     * Initialize the SendLayer SDK
     *
     * @param string $apiKey Your SendLayer API key
     * @param array $config Optional configuration array
     */
    public function __construct(string $apiKey, array $config = [])
    {
        $this->client = new BaseClient($apiKey, $config);
        $this->Emails = new Emails($this->client);
        $this->Webhooks = new Webhooks($this->client);
        $this->Events = new Events($this->client);
    }
} 