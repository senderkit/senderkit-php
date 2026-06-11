<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

final class SmsContent implements Content
{
    public function __construct(public readonly string $body)
    {
    }

    public function channel(): Channel
    {
        return Channel::Sms;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['body' => $this->body];
    }
}
