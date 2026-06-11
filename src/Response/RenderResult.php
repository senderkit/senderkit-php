<?php

declare(strict_types=1);

namespace SenderKit\Response;

use SenderKit\Enum\Channel;

final class RenderResult
{
    /**
     * @param array<string,mixed> $output
     * @param list<string> $missing
     */
    public function __construct(
        public readonly Channel $channel,
        public readonly array $output,
        public readonly array $missing,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $output = is_array($data['output'] ?? null) ? $data['output'] : [];
        /** @var array<string,mixed> $output */
        $missing = is_array($data['missing'] ?? null) ? $data['missing'] : [];
        /** @var list<string> $missing */
        return new self(
            Hydrate::channel($data, 'channel'),
            $output,
            $missing,
        );
    }
}
