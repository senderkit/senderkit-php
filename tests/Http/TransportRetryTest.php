<?php

declare(strict_types=1);

namespace SenderKit\Tests\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SenderKit\Exception\ApiException;
use SenderKit\Http\Transport;
use SenderKit\Tests\Support\RecordingClient;

final class TransportRetryTest extends TestCase
{
    /** A Transport that records sleeps instead of sleeping. */
    private function transport(RecordingClient $client, int $maxRetries, array &$sleeps): Transport
    {
        $f = new Psr17Factory();
        return new class ('sk_test_x', 'https://api.senderkit.com', 30000, $maxRetries, $client, $f, $f, $sleeps) extends Transport {
            /** @param array<int,int> $sleeps */
            public function __construct($k, $b, $t, $m, $c, $rf, $sf, private array &$sleeps)
            {
                parent::__construct($k, $b, $t, $m, $c, $rf, $sf);
            }
            protected function sleepMs(int $ms): void
            {
                $this->sleeps[] = $ms;
            }
        };
    }

    public function test_retries_on_500_then_succeeds(): void
    {
        $sleeps = [];
        $client = new RecordingClient([
            new Response(500, [], '{"error":{"message":"boom"}}'),
            new Response(202, [], '{"id":"msg_1","status":"queued","livemode":false}'),
        ]);
        $t = $this->transport($client, 2, $sleeps);
        $data = $t->request('POST', '/v1/send', body: []);
        $this->assertSame('msg_1', $data['id']);
        $this->assertCount(2, $client->requests);
        $this->assertCount(1, $sleeps);
    }

    public function test_does_not_retry_501(): void
    {
        $sleeps = [];
        $client = new RecordingClient([new Response(501, [], '{"error":{"message":"nope"}}')]);
        $t = $this->transport($client, 2, $sleeps);
        $this->expectException(ApiException::class);
        $t->request('GET', '/v1/context');
    }

    public function test_honors_retry_after_on_429(): void
    {
        $sleeps = [];
        $client = new RecordingClient([
            new Response(429, ['Retry-After' => '2'], '{"error":{"message":"slow"}}'),
            new Response(200, [], '{"mode":"test"}'),
        ]);
        $t = $this->transport($client, 1, $sleeps);
        $t->request('GET', '/v1/context');
        $this->assertSame([2000], $sleeps);
    }

    public function test_gives_up_after_max_retries(): void
    {
        $sleeps = [];
        $client = new RecordingClient([
            new Response(503, [], '{}'),
            new Response(503, [], '{}'),
        ]);
        $t = $this->transport($client, 1, $sleeps);
        $this->expectException(ApiException::class);
        $t->request('GET', '/v1/context');
    }

    public function test_retry_resends_full_body_not_empty(): void
    {
        $sleeps = [];
        $client = new RecordingClient([
            new Response(500, [], '{"error":{"message":"boom"}}'),
            new Response(202, [], '{"id":"msg_1","status":"queued","livemode":false}'),
        ], consumeBody: true);
        $t = $this->transport($client, 2, $sleeps);

        $t->request('POST', '/v1/send', body: ['template' => 'welcome', 'to' => 'a@b.com']);

        $this->assertCount(2, $client->bodies);
        $expected = '{"template":"welcome","to":"a@b.com"}';
        $this->assertSame($expected, $client->bodies[0]);
        $this->assertSame($expected, $client->bodies[1], 'retried request must resend the full body, not an empty stream');
    }
}
