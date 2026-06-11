<?php

declare(strict_types=1);

namespace SenderKit\Response;

use SenderKit\Exception\SenderKitException;

final class BatchResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly int $index,
        public readonly ?SendResult $result = null,
        public readonly ?SenderKitException $error = null,
    ) {
    }

    public static function success(int $index, SendResult $result): self
    {
        return new self(true, $index, $result, null);
    }

    public static function failure(int $index, SenderKitException $error): self
    {
        return new self(false, $index, null, $error);
    }
}
