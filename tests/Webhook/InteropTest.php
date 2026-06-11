<?php

declare(strict_types=1);

namespace SenderKit\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use SenderKit\Exception\SignatureVerificationException;
use SenderKit\Webhook\WebhookVerifier;

final class InteropTest extends TestCase
{
    private function sign(string $secret, int $t, string $body): string
    {
        $script = __DIR__ . '/../../../../tools/sign-webhook.mjs';
        if (!is_file($script)) {
            $this->markTestSkipped('signer script missing');
        }
        $cmd = sprintf(
            'node %s %s %d %s 2>/dev/null',
            escapeshellarg($script),
            escapeshellarg($secret),
            $t,
            escapeshellarg($body),
        );
        $out = @shell_exec($cmd);
        if (!is_string($out) || $out === '') {
            $this->markTestSkipped('node unavailable');
        }

        return trim($out);
    }

    public function test_php_verifier_accepts_node_signature(): void
    {
        $secret = 'whsec_interop';
        $t = 1_700_000_000;
        $body = '{"type":"message.delivered","id":"msg_1"}';
        $header = $this->sign($secret, $t, $body);

        $event = (new WebhookVerifier())->verify(
            rawBody: $body,
            signatureHeader: $header,
            secret: $secret,
            toleranceSeconds: PHP_INT_MAX,
            now: $t,
        );
        $this->assertSame('msg_1', $event->payload['id']);
    }

    public function test_php_verifier_rejects_node_signature_for_mutated_body(): void
    {
        $secret = 'whsec_interop';
        $t = 1_700_000_000;
        $header = $this->sign($secret, $t, '{"a":1}');

        $this->expectException(SignatureVerificationException::class);
        (new WebhookVerifier())->verify(
            rawBody: '{"a":2}',
            signatureHeader: $header,
            secret: $secret,
            toleranceSeconds: PHP_INT_MAX,
            now: $t,
        );
    }
}
