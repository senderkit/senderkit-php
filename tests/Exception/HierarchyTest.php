<?php

declare(strict_types=1);

namespace SenderKit\Tests\Exception;

use PHPUnit\Framework\TestCase;
use SenderKit\Exception\{SenderKitException, ApiException, AuthenticationException,
    ValidationException, RateLimitException, TimeoutException, NetworkException};

final class HierarchyTest extends TestCase
{
    public function test_subclassing(): void
    {
        $this->assertInstanceOf(SenderKitException::class, new ApiException('x', 500));
        $this->assertInstanceOf(ApiException::class, new AuthenticationException('x', 401));
        $this->assertInstanceOf(ApiException::class, new ValidationException('x', 422));
        $this->assertInstanceOf(ApiException::class, new RateLimitException('x', 429));
        $this->assertInstanceOf(SenderKitException::class, new TimeoutException('x'));
        $this->assertInstanceOf(SenderKitException::class, new NetworkException('x'));
    }

    public function test_api_exception_carries_fields(): void
    {
        $e = new ApiException('bad', 422, code: 'invalid', issues: ['to' => 'required'], requestId: 'req_1');
        $this->assertSame(422, $e->status);
        $this->assertSame('invalid', $e->apiCode);
        $this->assertSame(422, $e->getCode());
        $this->assertSame(['to' => 'required'], $e->issues);
        $this->assertSame('req_1', $e->requestId);
    }

    public function test_rate_limit_retry_after(): void
    {
        $e = new RateLimitException('slow down', 429, retryAfterMs: 1500);
        $this->assertSame(1500, $e->retryAfterMs);
    }
}
