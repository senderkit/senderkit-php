<?php

declare(strict_types=1);

namespace SenderKit\Exception;

final class TimeoutException extends SenderKitException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
