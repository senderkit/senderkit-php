<?php

declare(strict_types=1);

namespace SenderKit\Response;

final class TemplateVersion
{
    /**
     * @param array<string,mixed>|null $variables
     */
    public function __construct(
        public readonly int $versionNumber,
        public readonly ?array $variables,
        public readonly ?string $publishedAt,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string,mixed>|null $variables */
        $variables = is_array($data['variables'] ?? null) ? $data['variables'] : null;

        return new self(
            Hydrate::int($data, 'versionNumber'),
            $variables,
            Hydrate::nullableString($data, 'publishedAt'),
        );
    }
}
