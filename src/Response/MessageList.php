<?php

declare(strict_types=1);

namespace SenderKit\Response;

final class MessageList
{
    /** @param list<Message> $data */
    public function __construct(
        public readonly array $data,
        public readonly ?string $nextCursor,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $rows = is_array($data['data'] ?? null) ? $data['data'] : [];
        /** @var list<array<string,mixed>> $rows */
        $messages = array_map(
            static fn (array $m) => Message::fromArray($m),
            $rows,
        );
        return new self($messages, Hydrate::nullableString($data, 'nextCursor'));
    }
}
