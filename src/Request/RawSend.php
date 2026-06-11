<?php

declare(strict_types=1);

namespace SenderKit\Request;

final class RawSend
{
    /**
     * @param array<string,mixed>|null $vars
     * @param array<string,string|int|bool|float>|null $metadata
     */
    public function __construct(
        public readonly string $to,
        public readonly Content $content,
        public readonly ?string $from = null,
        public readonly ?bool $interpolate = null,
        public readonly ?array $vars = null,
        public readonly ?array $metadata = null,
        public readonly \DateTimeInterface|string|null $scheduledAt = null,
        public readonly ?string $idempotencyKey = null,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'channel' => $this->content->channel()->value,
            'to' => $this->to,
            'content' => $this->content->toArray(),
            'from' => $this->from,
            'interpolate' => $this->interpolate,
            'vars' => Serialize::record($this->vars),
            'metadata' => Serialize::record($this->metadata),
            'scheduledAt' => Serialize::dateTime($this->scheduledAt),
        ], static fn ($v) => $v !== null);
    }
}
