<?php

use PHPUnit\Framework\TestCase;
use SendLayer\SendLayer;
use SendLayer\Email\Emails;
use SendLayer\Events\Events;
use SendLayer\Webhooks\Webhooks;

class SendLayerTest extends TestCase
{
    public function testInitializationSetsClients()
    {
        $sdk = new SendLayer('test_api_key');
        $this->assertInstanceOf(Emails::class, $sdk->Emails);
        $this->assertInstanceOf(Webhooks::class, $sdk->Webhooks);
        $this->assertInstanceOf(Events::class, $sdk->Events);
    }
} 