<?php

use PHPUnit\Framework\TestCase;
use SendLayer\Events\Events;
use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerValidationException;

class EventsTest extends TestCase
{
    private function createClientMock()
    {
        $mock = $this->getMockBuilder(BaseClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeRequest'])
            ->getMock();
        $mock->method('makeRequest')->willReturn([
            'Events' => [],
            'TotalRecords' => 0,
        ]);
        return $mock;
    }

    public function testInvalidDateRange()
    {
        $this->expectException(SendLayerValidationException::class);
        $client = $this->createClientMock();
        $events = new Events($client);
        $start = new DateTime('2024-01-02');
        $end = new DateTime('2024-01-01');
        $events->get($start, $end);
    }

    public function testInvalidEventName()
    {
        $this->expectException(SendLayerValidationException::class);
        $events = new Events($this->createClientMock());
        $events->get(null, null, 'bad_event');
    }

    public function testInvalidRetrieveCount()
    {
        $this->expectException(SendLayerValidationException::class);
        $events = new Events($this->createClientMock());
        $events->get(null, null, null, null, null, 0);
    }

    public function testValidCallReturnsStructure()
    {
        $events = new Events($this->createClientMock());
        $result = $events->get();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalRecords', $result);
        $this->assertArrayHasKey('events', $result);
    }
} 