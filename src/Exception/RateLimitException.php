<?php

declare(strict_types=1);

namespace SenderKit\Exception;

final class RateLimitException extends ApiException
{
    /** @param array<string,mixed>|list<mixed>|null $issues */
    public function __construct(
        string $message,
        int $status,
        ?string $code = null,
        array|null $issues = null,
        ?string $requestId = null,
        public readonly ?int $retryAfterMs = null,
    ) {
        parent::__construct($message, $status, $code, $issues, $requestId);
    }
}
