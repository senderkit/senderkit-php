<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

final class TemplateSend
{
    /**
     * @param array<string,mixed>|null $vars
     * @param array<string,string|int|bool|float>|null $metadata
     * @param list<string>|null $cc
     * @param list<string>|null $bcc
     * @param list<Attachment>|null $attachments
     */
    public function __construct(
        public readonly string $template,
        public readonly string $to,
        public readonly ?array $vars = null,
        public readonly ?int $version = null,
        public readonly ?Channel $channel = null,
        public readonly ?array $metadata = null,
        public readonly \DateTimeInterface|string|null $scheduledAt = null,
        public readonly ?array $cc = null,
        public readonly ?array $bcc = null,
        public readonly ?string $replyTo = null,
        public readonly ?array $attachments = null,
        public readonly ?string $idempotencyKey = null,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'template' => $this->template,
            'to' => $this->to,
            'vars' => Serialize::record($this->vars),
            'version' => $this->version,
            'channel' => $this->channel?->value,
            'metadata' => Serialize::record($this->metadata),
            'scheduledAt' => Serialize::dateTime($this->scheduledAt),
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'replyTo' => $this->replyTo,
            'attachments' => $this->attachments === null
                ? null
                : array_map(static fn (Attachment $a) => $a->toArray(), $this->attachments),
        ], static fn ($v) => $v !== null);
    }
}
