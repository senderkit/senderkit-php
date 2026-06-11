<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

final class EmailContent implements Content
{
    /**
     * @param list<string>|null $cc
     * @param list<string>|null $bcc
     * @param list<Attachment>|null $attachments
     */
    public function __construct(
        public readonly string $subject,
        public readonly string $html,
        public readonly ?string $preheader = null,
        public readonly ?string $text = null,
        public readonly ?array $cc = null,
        public readonly ?array $bcc = null,
        public readonly ?string $replyTo = null,
        public readonly ?array $attachments = null,
    ) {
    }

    public function channel(): Channel
    {
        return Channel::Email;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'subject' => $this->subject,
            'html' => $this->html,
            'preheader' => $this->preheader,
            'text' => $this->text,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'replyTo' => $this->replyTo,
            'attachments' => $this->attachments === null
                ? null
                : array_map(static fn (Attachment $a) => $a->toArray(), $this->attachments),
        ], static fn ($v) => $v !== null);
    }
}
