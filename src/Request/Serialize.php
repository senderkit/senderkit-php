<?php

declare(strict_types=1);

namespace SenderKit\Request;

final class Serialize
{
    private function __construct()
    {
    }

    public static function dateTime(\DateTimeInterface|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof \DateTimeInterface ? $value->format(\DateTimeInterface::ATOM) : $value;
    }

    /**
     * Record-typed API fields must serialize as JSON objects; an empty PHP
     * array would encode as [] and be rejected, so it becomes \stdClass ({}).
     *
     * @param array<string,mixed>|null $value
     * @return \stdClass|array<string,mixed>|null
     */
    public static function record(?array $value): \stdClass|array|null
    {
        if ($value === null) {
            return null;
        }

        return $value === [] ? new \stdClass() : $value;
    }
}
