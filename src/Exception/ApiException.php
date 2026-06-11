<?php

declare(strict_types=1);

namespace SenderKit\Exception;

class ApiException extends SenderKitException
{
    /** API-level error code string (distinct from the HTTP status). */
    public readonly ?string $apiCode;

    /** @var array<string,mixed>|list<mixed>|null */
    public readonly array|null $issues;

    /** @param array<string,mixed>|list<mixed>|null $issues */
    public function __construct(
        string $message,
        public readonly int $status,
        ?string $code = null,
        array|null $issues = null,
        public readonly ?string $requestId = null,
    ) {
        // Pass $status as the Exception $code so that the final getCode()
        // satisfies the Throwable::getCode(): int contract.
        parent::__construct($message, $status);
        $this->apiCode = $code;
        $this->issues = $issues;
    }
}
