<?php

declare(strict_types=1);

namespace SenderKit\Webhook;

final class WebhookEvent
{
    /** @param array<string,mixed> $payload */
    public function __construct(
        public readonly ?string $type,
        public readonly ?string $deliveryId,
        public readonly array $payload,
        public readonly int $timestamp,
    ) {
    }
}
