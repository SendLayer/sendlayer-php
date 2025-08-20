<a href="https://sendlayer.com">
<picture>
  <source media="(prefers-color-scheme: light)" srcset="https://sendlayer.com/wp-content/themes/sendlayer-theme/assets/images/svg/logo-dark.svg">
  <source media="(prefers-color-scheme: dark)" srcset="https://sendlayer.com/wp-content/themes/sendlayer-theme/assets/images/svg/logo-light.svg">
  <img alt="SendLayer Logo" width="200px" src="https://sendlayer.com/wp-content/themes/sendlayer-theme/assets/images/svg/logo-light.svg">
</picture>
</a>

### SendLayer PHP SDK

The official PHP SDK for interacting with the SendLayer API, providing a simple and intuitive interface for sending emails, managing webhooks, and retrieving email events.

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)

## Features

- **Email Sending**: Send transactional emails with support for HTML, text, attachments, and advanced options
- **Webhook Management**: Create, retrieve, and delete webhooks for real-time event notifications
- **Event Tracking**: Retrieve email events and analytics data
- **Error Handling**: Comprehensive exception handling with specific error types
- **Validation**: Built-in validation for email addresses, URLs, and API parameters
- **File Attachments**: Support for both local and remote file attachments
- **Modern PHP**: Built with PHP 7.4+ features and PSR-4 autoloading

## Installation

### Using Composer (Recommended)

```bash
composer require sendlayer/sendlayer-php
```

### Manual Installation

1. Download the SDK
2. Include the autoloader in your project
3. Install dependencies: `composer install`

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use SendLayer\SendLayer;
use SendLayer\Exceptions\SendLayerException;

// Initialize the SDK with your API key
$sendlayer = new SendLayer('your-api-key-here');

try {
    // Send a simple email
    $response = $sendlayer->Emails->send(
        from: 'sender@example.com',
        to: 'recipient@example.com',
        subject: 'Test Email',
        text: 'This is a test email sent using the SendLayer PHP SDK'
    );
    
    echo "Email sent successfully! Message ID: " . $response['MessageID'];
    
} catch (SendLayerException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Configuration (Optional)

You can pass additional configuration options when initializing the SDK:

```php
$config = [
    'timeout' => 30,                    // HTTP timeout in seconds
    'attachmentURLTimeout' => 30000,    // Attachment URL timeout in milliseconds
    'guzzle' => [                       // Additional Guzzle HTTP client options
        'verify' => false,              // Disable SSL verification (not recommended for production)
        'proxy' => 'http://proxy:8080'  // Use proxy
    ]
];

$sendlayer = new SendLayer('your-api-key-here', $config);
```

## Email Sending

### Basic Email

```php
$response = $sendlayer->Emails->send(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Welcome!',
    text: 'Welcome to our platform!'
);
```

### HTML Email

```php
$response = $sendlayer->Emails->send(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Welcome!',
    html: '<h1>Welcome!</h1><p>Welcome to our platform!</p>'
);
```

### Email with Sender Name

```php
$response = $sendlayer->Emails->send(
    from: ['email' => 'sender@example.com', 'name' => 'John Doe'],
    to: 'recipient@example.com',
    subject: 'Welcome!',
    text: 'Welcome to our platform!'
);
```

### Multiple Recipients

```php
$response = $sendlayer->Emails->send(
    from: 'sender@example.com',
    to: ['user1@example.com', 'user2@example.com'],
    subject: 'Welcome!',
    text: 'Welcome to our platform!'
);
```

### Email with CC and BCC

```php
$response = $sendlayer->Emails->send(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Welcome!',
    text: 'Welcome to our platform!',
    cc: 'cc@example.com',
    bcc: 'bcc@example.com'
);
```

### Email with Attachments

```php
$response = $sendlayer->Emails->send(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Document attached',
    text: 'Please find the attached document.',
    attachments: [
        [
            'path' => '/path/to/document.pdf',
            'type' => 'application/pdf'
        ],
        [
            'path' => 'https://example.com/image.jpg',
            'type' => 'image/jpeg'
        ]
    ]
);
```

### Email with Custom Headers and Tags

```php
$response = $sendlayer->Emails->send(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Welcome!',
    text: 'Welcome to our platform!',
    headers: [
        'X-Custom-Header' => 'Custom Value',
        'X-Priority' => '1'
    ],
    tags: ['welcome', 'onboarding']
);
```

## Webhook Management

### Create a Webhook

```php
$response = $sendlayer->Webhooks->create(
    url: 'https://your-domain.com/webhook',
    event: 'delivery'
);
```

### Get All Webhooks

```php
$webhooks = $sendlayer->Webhooks->get();
```

### Delete a Webhook

```php
$sendlayer->Webhooks->delete(webhookId: 123);
```

## Event Tracking

### Get All Events

```php
$events = $sendlayer->Events->get();
```

### Get Events with Filters

```php
$events = $sendlayer->Events->get(
    startDate: new DateTime('2024-01-01'),
    endDate: new DateTime('2024-01-31'),
    event: 'delivered',
    retrieveCount: 50
);
```

### Get Events for Specific Message

```php
$events = $sendlayer->Events->get(
    messageId: 'message-id-here'
);
```

## Error Handling

The SDK provides specific exception types for different error scenarios:

```php
use SendLayer\Exceptions\SendLayerException;

try {
    $response = $sendlayer->Emails->send(/* ... */);
} catch (SendLayerException $e) {
    // Handle other SendLayer errors
    echo "Error: " . $e->getMessage();
}
```

## Exception Types

- `SendLayerException`: Base exception for all SendLayer errors
- `SendLayerAuthenticationException`: Invalid API key or authentication issues
- `SendLayerValidationException`: Invalid parameters or validation errors
- `SendLayerAPIException`: API-specific errors with status code and response data
- `SendLayerNotFoundException`: Resource not found (404 errors)
- `SendLayerRateLimitException`: Rate limit exceeded (429 errors)
- `SendLayerInternalServerException`: Server errors (5xx errors)

## Supported Events

### Webhook Events
- `bounce`: Email bounced
- `click`: Link was clicked
- `open`: Email was opened
- `unsubscribe`: User unsubscribed
- `complaint`: User marked as spam
- `delivery`: Email was delivered

### Event Tracking Events
- `accepted`: Email was accepted by the server
- `rejected`: Email was rejected
- `delivered`: Email was delivered
- `opened`: Email was opened
- `clicked`: Link was clicked
- `unsubscribed`: User unsubscribed
- `complained`: User marked as spam
- `failed`: Email delivery failed

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP Client 7.0 or higher
- JSON extension
- cURL extension (recommended)

## License

This SDK is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For support, please contact:
- Email: support@sendlayer.com
- Documentation: https://developers.sendlayer.com
- GitHub Issues: https://github.com/sendlayer/sendlayer-php/issues

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history. 