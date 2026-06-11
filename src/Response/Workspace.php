<?php

declare(strict_types=1);

namespace SenderKit\Response;

final class Workspace
{
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $name,
    ) {
    }
}
