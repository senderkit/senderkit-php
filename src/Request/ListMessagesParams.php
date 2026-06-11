<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

final class ListMessagesParams
{
    /** @param array<string,string|int|bool|float>|null $metadata */
    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?string $cursor = null,
        public readonly ?string $status = null,
        public readonly ?Channel $channel = null,
        public readonly ?string $template = null,
        public readonly ?array $metadata = null,
    ) {
    }

    /** @return array<string,string> */
    public function toQuery(): array
    {
        $q = array_filter([
            'limit' => $this->limit === null ? null : (string) $this->limit,
            'cursor' => $this->cursor,
            'status' => $this->status,
            'channel' => $this->channel?->value,
            'template' => $this->template,
        ], static fn ($v) => $v !== null);

        foreach ($this->metadata ?? [] as $key => $value) {
            $q["metadata[{$key}]"] = (string) $value;
        }

        return $q;
    }
}
