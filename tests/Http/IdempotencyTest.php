<?php

declare(strict_types=1);

namespace SenderKit\Tests\Http;

use PHPUnit\Framework\TestCase;
use SenderKit\Http\Idempotency;

final class IdempotencyTest extends TestCase
{
    public function test_generates_v4_uuid(): void
    {
        $uuid = Idempotency::uuidv4();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
        $this->assertNotSame($uuid, Idempotency::uuidv4());
    }
}
