<?php

declare(strict_types=1);

namespace SenderKit\Request;

use SenderKit\Enum\Channel;

interface Content
{
    public function channel(): Channel;

    /** @return array<string,mixed> */
    public function toArray(): array;
}
