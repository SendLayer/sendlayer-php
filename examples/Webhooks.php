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
    // Create a new webhook
    $response = $sendlayer->Webhooks->create(
        url: 'https://your-domain.com/webhook',
        event: 'delivery'
    );
    echo "✅ Webhook created successfully! Webhook ID: " . $response['WebhookID'] . "\n";

    // Get all webhooks
    $webhooks = $sendlayer->Webhooks->get();
    echo "📋 Found " . count($webhooks['Webhooks'] ?? []) . " webhooks:\n";
    
    foreach ($webhooks['Webhooks'] ?? [] as $webhook) {
        echo json_encode($webhook, JSON_PRETTY_PRINT) . "\n";
    }

    // Delete a webhook (uncomment and replace with actual webhook ID)
    $sendlayer->Webhooks->delete(webhookId: 24982);
    echo "🗑️ Webhook deleted successfully!\n";

} catch (SendLayerException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 