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
    // Get all events from the last 7 days
    $events = $sendlayer->Events->get();
    
    echo "📊 Found {$events['totalRecords']} events in the last 7 days:\n";
    
    foreach ($events['events'] as $event) {
        echo json_encode($event, JSON_PRETTY_PRINT) . "\n";
    }

    // Get specific event types
    $deliveredEvents = $sendlayer->Events->get(
        startDate: new DateTime('-30 days'),
        endDate: new DateTime(),
        event: 'delivered',
        retrieveCount: 20
    );
    
    echo "\n📈 Found {$deliveredEvents['totalRecords']} delivered events in the last 30 days\n";
    foreach ($deliveredEvents['events'] as $event) {
        echo json_encode($event, JSON_PRETTY_PRINT) . "\n";
    }

    // Get events for a specific message (uncomment and replace with actual message ID)
    $messageEvents = $sendlayer->Events->get(
        messageId: 'your-message-id-here'
    );
    echo "📧 Found " . count($messageEvents['events']) . " events for message\n";

} catch (SendLayerException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 