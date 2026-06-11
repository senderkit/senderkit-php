<?php

declare(strict_types=1);

namespace SenderKit\Response;

use SenderKit\Enum\Channel;
use SenderKit\Exception\SenderKitException;

/**
 * @internal Coerces loosely-typed decoded-JSON values into scalar types,
 * mirroring the defensive casting the SDK relies on. Narrowing with is_scalar
 * keeps PHPStan (level max) happy — casts are only applied to non-mixed types.
 */
final class Hydrate
{
    private function __construct()
    {
    }

    /** @param array<string,mixed> $data */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $v = $data[$key] ?? null;

        return is_scalar($v) ? (string) $v : $default;
    }

    /** @param array<string,mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        $v = $data[$key] ?? null;
        if ($v === null) {
            return null;
        }

        return is_scalar($v) ? (string) $v : null;
    }

    /** @param array<string,mixed> $data */
    public static function int(array $data, string $key, int $default = 0): int
    {
        $v = $data[$key] ?? null;

        return is_scalar($v) ? (int) $v : $default;
    }

    /** @param array<string,mixed> $data */
    public static function bool(array $data, string $key): bool
    {
        $v = $data[$key] ?? null;

        return is_scalar($v) ? (bool) $v : false;
    }

    /** @param array<string,mixed> $data */
    public static function channel(array $data, string $key): Channel
    {
        $value = self::string($data, $key);

        return Channel::tryFrom($value)
            ?? throw new SenderKitException(
                sprintf('Unknown channel "%s" in API response.', $value),
            );
    }
}
