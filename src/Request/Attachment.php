<?php

declare(strict_types=1);

namespace SenderKit\Request;

final class Attachment
{
    public function __construct(
        public readonly string $filename,
        public readonly string $contentType,
        public readonly string $content,      // base64-encoded
        public readonly ?bool $inline = null,
        public readonly ?string $contentId = null,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'filename' => $this->filename,
            'contentType' => $this->contentType,
            'content' => $this->content,
            'inline' => $this->inline,
            'contentId' => $this->contentId,
        ], static fn ($v) => $v !== null);
    }
}
