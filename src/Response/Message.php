<?php

declare(strict_types=1);

namespace SenderKit\Response;

use SenderKit\Enum\Channel;

final class Message
{
    public function __construct(
        public readonly string $id,
        public readonly string $publicId,
        public readonly string $status,
        public readonly Channel $channel,
        public readonly ?string $templateSlug,
        public readonly string $recipient,
        public readonly string $createdAt,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Hydrate::string($data, 'id'),
            Hydrate::string($data, 'publicId'),
            Hydrate::string($data, 'status'),
            Hydrate::channel($data, 'channel'),
            Hydrate::nullableString($data, 'templateSlug'),
            Hydrate::string($data, 'recipient'),
            Hydrate::string($data, 'createdAt'),
        );
    }
}
