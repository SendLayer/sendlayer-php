<?php

use PHPUnit\Framework\TestCase;
use SendLayer\Webhooks\Webhooks;
use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerValidationException;

class WebhooksTest extends TestCase
{
    private function createClientMock()
    {
        $mock = $this->getMockBuilder(BaseClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeRequest'])
            ->getMock();
        $mock->method('makeRequest')->willReturn([]);
        return $mock;
    }

    public function testInvalidUrl()
    {
        $this->expectException(SendLayerValidationException::class);
        $webhooks = new Webhooks($this->createClientMock());
        $webhooks->create('not-a-url', 'delivery');
    }

    public function testInvalidEventName()
    {
        $this->expectException(SendLayerValidationException::class);
        $webhooks = new Webhooks($this->createClientMock());
        $webhooks->create('https://example.com/webhook', 'bad_event');
    }

    public function testInvalidWebhookId()
    {
        $this->expectException(SendLayerValidationException::class);
        $webhooks = new Webhooks($this->createClientMock());
        $webhooks->delete(0);
    }

    public function testCreateValidWebhook()
    {
        $webhooks = new Webhooks($this->createClientMock());
        $result = $webhooks->create('https://example.com/webhook', 'delivery');
        $this->assertIsArray($result);
    }
} 