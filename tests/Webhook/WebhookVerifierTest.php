<?php

declare(strict_types=1);

namespace SenderKit\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use SenderKit\Exception\SignatureVerificationException;
use SenderKit\Webhook\{WebhookEvent, WebhookVerifier};

final class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'whsec_test';

    private function header(string $body, int $t): string
    {
        $mac = hash_hmac('sha256', $t . '.' . $body, self::SECRET);
        return "t={$t},v1={$mac}";
    }

    public function test_verifies_valid_signature(): void
    {
        $body = '{"type":"message.delivered"}';
        $t = 1_700_000_000;
        $event = (new WebhookVerifier())->verify(
            rawBody: $body,
            signatureHeader: $this->header($body, $t),
            secret: self::SECRET,
            toleranceSeconds: 300,
            now: $t + 10,
            eventType: 'message.delivered',
            deliveryId: 'del_1',
        );
        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertSame('message.delivered', $event->type);
        $this->assertSame('del_1', $event->deliveryId);
        $this->assertSame(['type' => 'message.delivered'], $event->payload);
        $this->assertSame($t, $event->timestamp);
    }

    public function test_rejects_tampered_body(): void
    {
        $t = 1_700_000_000;
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: '{"type":"tampered"}',
            signatureHeader: $this->header('{"type":"original"}', $t),
            secret: self::SECRET,
            now: $t,
        );
    }

    public function test_rejects_stale_timestamp(): void
    {
        $body = '{}';
        $t = 1_700_000_000;
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: $body,
            signatureHeader: $this->header($body, $t),
            secret: self::SECRET,
            toleranceSeconds: 300,
            now: $t + 1000,
        );
    }

    public function test_rejects_malformed_header(): void
    {
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(rawBody: '{}', signatureHeader: 'garbage', secret: self::SECRET, now: 1);
    }

    public function test_rejects_wrong_secret(): void
    {
        $body = '{"type":"x"}';
        $t = 1_700_000_000;
        $forged = 'whsec_attacker';
        $mac = hash_hmac('sha256', $t . '.' . $body, $forged);
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: $body,
            signatureHeader: "t={$t},v1={$mac}",
            secret: self::SECRET,
            now: $t,
        );
    }

    public function test_rejects_future_timestamp(): void
    {
        $body = '{}';
        $t = 1_700_000_000;
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: $body,
            signatureHeader: $this->header($body, $t),
            secret: self::SECRET,
            toleranceSeconds: 300,
            now: $t - 1000, // signed 1000s in the "future" relative to now
        );
    }

    public function test_rejects_non_digit_timestamp(): void
    {
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: '{}',
            signatureHeader: 't=abc,v1=deadbeef',
            secret: self::SECRET,
            now: 1,
        );
    }

    public function test_rejects_scientific_notation_timestamp(): void
    {
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: '{}',
            signatureHeader: 't=1e3,v1=deadbeef',
            secret: self::SECRET,
            now: 1,
        );
    }

    public function test_rejects_missing_v1(): void
    {
        $t = 1_700_000_000;
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(rawBody: '{}', signatureHeader: "t={$t}", secret: self::SECRET, now: $t);
    }

    public function test_rejects_empty_v1(): void
    {
        $t = 1_700_000_000;
        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(rawBody: '{}', signatureHeader: "t={$t},v1=", secret: self::SECRET, now: $t);
    }
}
