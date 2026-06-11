<?php

declare(strict_types=1);

namespace SenderKit\Response;

final class Context
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly string $mode,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $w = is_array($data['workspace'] ?? null) ? $data['workspace'] : [];
        /** @var array<string,mixed> $w */
        return new self(
            new Workspace(Hydrate::string($w, 'id'), Hydrate::string($w, 'slug'), Hydrate::string($w, 'name')),
            Hydrate::string($data, 'mode'),
        );
    }
}
