<?php

use PHPUnit\Framework\TestCase;
use SendLayer\Email\Emails;
use SendLayer\Base\BaseClient;
use SendLayer\Exceptions\SendLayerValidationException;
use SendLayer\Exceptions\SendLayerException;

class EmailsTest extends TestCase
{
    private function createClientMock()
    {
        $mock = $this->getMockBuilder(BaseClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeRequest'])
            ->getMock();
        $mock->method('makeRequest')->willReturn(['MessageID' => 'test']);
        return $mock;
    }

    public function testSendRequiresTextOrHtml()
    {
        $this->expectException(SendLayerValidationException::class);
        $emails = new Emails($this->createClientMock());
        $emails->send('sender@example.com', 'to@example.com', 'Subject');
    }

    public function testInvalidRecipientEmail()
    {
        $this->expectException(SendLayerValidationException::class);
        $emails = new Emails($this->createClientMock());
        $emails->send('sender@example.com', 'invalid-email', 'Subject', 'text body');
    }

    public function testInvalidCcAndBccEmail()
    {
        $this->expectException(SendLayerValidationException::class);
        $emails = new Emails($this->createClientMock());
        $emails->send('sender@example.com', 'to@example.com', 'Subject', 'text body', null, 'bad-email');
    }

    public function testAttachmentFileNotFound()
    {
        $this->expectException(SendLayerException::class);
        $emails = new Emails($this->createClientMock());
        $emails->send('sender@example.com', 'to@example.com', 'Subject', 'text body', null, null, null, [
            ['path' => 'nonexistent_file.txt', 'type' => 'text/plain']
        ]);
    }

    public function testTagsMustBeStrings()
    {
        $this->expectException(SendLayerValidationException::class);
        $emails = new Emails($this->createClientMock());
        $emails->send('sender@example.com', 'to@example.com', 'Subject', 'text body', null, null, null, null, null, null, [ 'valid', 123 ]);
    }

    public function testSuccessfulSendReturnsArray()
    {
        $emails = new Emails($this->createClientMock());
        $result = $emails->send(['email' => 'sender@example.com'], [['email' => 'to@example.com']], 'Subject', 'text body');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('MessageID', $result);
    }
} 