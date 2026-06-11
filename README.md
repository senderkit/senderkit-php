# SenderKit PHP SDK

The official PHP SDK for [SenderKit](https://senderkit.com) — send transactional email,
SMS, push, and web-push from PHP.

## Requirements

- PHP 8.1+
- Any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client (e.g. Guzzle or
  `symfony/http-client`) — auto-discovered, or inject your own.

## Install

```bash
composer require senderkit/senderkit-php
```

## Quick start

```php
use SenderKit\Client;
use SenderKit\Request\TemplateSend;

$sk = new Client(apiKey: getenv('SENDERKIT_API_KEY')); // sk_live_… or sk_test_…

$result = $sk->send(new TemplateSend(
    template: 'welcome',
    to: 'user@example.com',
    vars: ['name' => 'Ada'],
));

echo $result->id;     // msg_…
echo $result->status; // queued | scheduled
```

### Raw send

```php
use SenderKit\Request\{RawSend, EmailContent};

$sk->sendRaw(new RawSend(
    to: 'user@example.com',
    content: new EmailContent(subject: 'Receipt', html: '<p>Thanks for your order.</p>'),
    metadata: ['source' => 'checkout'],
));
```

### Webhooks

```php
use SenderKit\Webhook\WebhookVerifier;

$event = (new WebhookVerifier)->verify(
    rawBody: $rawRequestBody,
    signatureHeader: $request->header('X-SenderKit-Signature'),
    secret: getenv('SENDERKIT_WEBHOOK_SECRET'), // whsec_…
);

echo $event->type; // message.delivered, message.failed, …
```

## Documentation

- API reference: https://senderkit.com/docs
- OpenAPI spec: https://senderkit.com/openapi.yaml

## License

[MIT](LICENSE)
