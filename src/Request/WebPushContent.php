<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

final class WebPushContent implements Content
{
    /** @param array<string,string>|null $data */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $icon = null,
        public readonly ?string $clickUrl = null,
        public readonly ?array $data = null,
        public readonly ?int $badge = null,
    ) {
    }

    public function channel(): Channel
    {
        return Channel::WebPush;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'clickUrl' => $this->clickUrl,
            'data' => Serialize::record($this->data),
            'badge' => $this->badge,
        ], static fn ($v) => $v !== null);
    }
}
