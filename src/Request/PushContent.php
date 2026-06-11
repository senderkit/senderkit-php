<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

final class PushContent implements Content
{
    /** @param array<string,string>|null $data */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?array $data = null,
        public readonly ?int $badge = null,
        public readonly ?string $sound = null,
    ) {
    }

    public function channel(): Channel
    {
        return Channel::Push;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'data' => Serialize::record($this->data),
            'badge' => $this->badge,
            'sound' => $this->sound,
        ], static fn ($v) => $v !== null);
    }
}
