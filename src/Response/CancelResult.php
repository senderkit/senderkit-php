<?php

declare(strict_types=1);

namespace SenderKit\Response;

final class CancelResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Hydrate::string($data, 'id'),
            Hydrate::string($data, 'status'),
        );
    }
}
