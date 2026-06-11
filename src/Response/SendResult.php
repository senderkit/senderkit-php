<?php

declare(strict_types=1);

namespace SenderKit\Response;

final class SendResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly bool $livemode,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Hydrate::string($data, 'id'),
            Hydrate::string($data, 'status'),
            Hydrate::bool($data, 'livemode'),
        );
    }
}
