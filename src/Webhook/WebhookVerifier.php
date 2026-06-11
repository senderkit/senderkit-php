<?php

declare(strict_types=1);

namespace SenderKit\Webhook;

use SenderKit\Exception\SignatureVerificationException;

final class WebhookVerifier
{
    /**
     * @param string      $rawBody          Raw request body bytes (before any decoding).
     * @param string      $signatureHeader  Value of the X-SenderKit-Signature header.
     * @param string      $secret           Webhook signing secret (whsec_…).
     * @param int         $toleranceSeconds Maximum allowed clock skew in seconds (default 300).
     * @param int|null    $now              Current Unix timestamp; defaults to time().
     * @param string|null $eventType        Pass-through (e.g. the X-SenderKit-Event header); not parsed from the body.
     * @param string|null $deliveryId       Pass-through (e.g. the X-SenderKit-Delivery header); not parsed from the body.
     *
     * @throws SignatureVerificationException
     */
    public function verify(
        string $rawBody,
        string $signatureHeader,
        string $secret,
        int $toleranceSeconds = 300,
        ?int $now = null,
        ?string $eventType = null,
        ?string $deliveryId = null,
    ): WebhookEvent {
        [$timestamp, $signature] = $this->parseHeader($signatureHeader);

        $now ??= time();
        if (abs($now - $timestamp) > $toleranceSeconds) {
            throw new SignatureVerificationException('Webhook timestamp outside tolerance window.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        if (!hash_equals($expected, $signature)) {
            throw new SignatureVerificationException('Webhook signature mismatch.');
        }

        // Non-JSON body is allowed; payload falls back to [] (the HMAC already validated integrity).
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        return new WebhookEvent($eventType, $deliveryId, $payload, $timestamp);
    }

    /** @return array{0:int,1:string} */
    private function parseHeader(string $header): array
    {
        $timestamp = null;
        $signature = null;
        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }
            [$key, $value] = $pair;
            if ($key === 't') {
                $timestamp = ctype_digit($value) ? (int) $value : null;
            } elseif ($key === 'v1') {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null || $signature === '') {
            throw new SignatureVerificationException('Malformed signature header.');
        }

        return [$timestamp, $signature];
    }
}
