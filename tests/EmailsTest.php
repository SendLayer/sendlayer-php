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

    /**
     * Build a BaseClient mock that records the JSON payload handed to makeRequest(),
     * so tests can assert on the exact request body the SDK builds.
     *
     * @param array|null $captured Receives the 'json' payload of the request.
     */
    private function createCapturingClientMock(&$captured)
    {
        $mock = $this->getMockBuilder(BaseClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeRequest'])
            ->getMock();
        $mock->method('makeRequest')->willReturnCallback(
            function ($method, $endpoint, $options = []) use (&$captured) {
                $captured = $options['json'] ?? [];
                return ['MessageID' => 'test'];
            }
        );

        return $mock;
    }

    /**
     * Regression: passing both html and text must send both parts. The previous
     * if/else meant PlainContent was silently dropped whenever html was present.
     */
    public function testBothHtmlAndTextArePreserved()
    {
        $payload = null;
        $emails = new Emails($this->createCapturingClientMock($payload));
        $emails->send(
            'sender@example.com',
            'to@example.com',
            'Subject',
            'plain text fallback',
            '<p>html body</p>'
        );

        $this->assertArrayHasKey('HTMLContent', $payload);
        $this->assertArrayHasKey('PlainContent', $payload);
        $this->assertSame('<p>html body</p>', $payload['HTMLContent']);
        $this->assertSame('plain text fallback', $payload['PlainContent']);
        // HTML takes precedence for the declared content type.
        $this->assertSame('HTML', $payload['ContentType']);
    }

    public function testTextOnlySendsPlainContentOnly()
    {
        $payload = null;
        $emails = new Emails($this->createCapturingClientMock($payload));
        $emails->send('sender@example.com', 'to@example.com', 'Subject', 'text body');

        $this->assertSame('Text', $payload['ContentType']);
        $this->assertSame('text body', $payload['PlainContent']);
        $this->assertArrayNotHasKey('HTMLContent', $payload);
    }

    public function testHtmlOnlySendsHtmlContentOnly()
    {
        $payload = null;
        $emails = new Emails($this->createCapturingClientMock($payload));
        $emails->send('sender@example.com', 'to@example.com', 'Subject', null, '<p>html body</p>');

        $this->assertSame('HTML', $payload['ContentType']);
        $this->assertSame('<p>html body</p>', $payload['HTMLContent']);
        $this->assertArrayNotHasKey('PlainContent', $payload);
    }

    /**
     * "0" is a valid body but empty()-based checks treated it as missing.
     */
    public function testZeroStringContentIsNotTreatedAsEmpty()
    {
        $payload = null;
        $emails = new Emails($this->createCapturingClientMock($payload));
        $emails->send('sender@example.com', 'to@example.com', 'Subject', '0');

        $this->assertSame('Text', $payload['ContentType']);
        $this->assertSame('0', $payload['PlainContent']);
    }
}
