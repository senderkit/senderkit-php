<?php

declare(strict_types=1);

namespace SenderKit\Response;

use SenderKit\Enum\Channel;

final class Template
{
    public function __construct(
        public readonly string $slug,
        public readonly Channel $channel,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $updatedAt,
        public readonly ?TemplateVersion $currentVersion = null,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Hydrate::string($data, 'slug'),
            Hydrate::channel($data, 'channel'),
            Hydrate::nullableString($data, 'description'),
            Hydrate::string($data, 'status'),
            Hydrate::string($data, 'updatedAt'),
            isset($data['currentVersion']) && is_array($data['currentVersion'])
                ? TemplateVersion::fromArray($data['currentVersion'])
                : null,
        );
    }
}
