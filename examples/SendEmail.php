<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SendLayer\SendLayer;
use SendLayer\Exceptions\SendLayerException;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['SENDLAYER_API_KEY'] ?? 'your-api-key-here';
$sendlayer = new SendLayer($apiKey);


try {
    // Send a simple email
    $response = $sendlayer->Emails->send(
        from: 'paulie@example.com',
        to: 'recipient@example.com',
        subject: 'Test Email from PHP SDK',
        text: 'This is a test email sent using the SendLayer PHP SDK'
    );
    echo "✅ Simple email sent successfully! " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

    // Send an email with advanced options
    $resp = $sendlayer->Emails->send(
        from: ['email' => 'paulie@example.com', 'name' => 'Paulie Paloma'],
        to: [
                [ 'name' => 'John Doe', 'email' => 'johndoe@example.com'],
                [ 'name' => 'Pattie Paloma', 'email' => 'pattie@example.com']
            ],
        subject: 'Sending a Test Email With PHP SDK',
        html: '<h1>Hello!</h1><p>This is a test email with all options</p>',
        cc: ['cc@example.com'],
        bcc: ['bcc@example.com'],
        replyTo: 'reply-to@example.com',
        attachments: [
            [
                'path' => __DIR__ . '/document.pdf',
                'type' => 'application/pdf'
            ],
            [
                'path' => 'https://placehold.co/600x400.png',
                'type' => 'image/png'
            ]
        ],
        headers: [
            'X-Custom-Header' => 'Custom Value'
        ],
        tags: ['test', 'sdk']
    );
    echo "✅ Complex email sent successfully! Message ID: " . $resp['MessageID'] . "\n";

} catch (SendLayerException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 