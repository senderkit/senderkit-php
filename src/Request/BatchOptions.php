<?php

declare(strict_types=1);

namespace SenderKit\Request;

final class BatchOptions
{
    public function __construct(public readonly ?string $idempotencyKey = null)
    {
    }
}
