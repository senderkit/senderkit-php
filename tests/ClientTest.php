<?php

declare(strict_types=1);

namespace SenderKit\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SenderKit\Client;
use SenderKit\Request\BatchOptions;
use SenderKit\Request\RawSend;
use SenderKit\Request\SmsContent;
use SenderKit\Request\TemplateSend;
use SenderKit\Response\Context;
use SenderKit\Response\SendResult;
use SenderKit\Tests\Support\RecordingClient;

final class ClientTest extends TestCase
{
    private function client(RecordingClient $http): Client
    {
        $f = new Psr17Factory();
        return new Client(apiKey: 'sk_test_abc', httpClient: $http, requestFactory: $f, streamFactory: $f);
    }

    public function test_mode_derived_from_key(): void
    {
        $live = new Client(apiKey: 'sk_live_x', httpClient: new RecordingClient([]));
        $test = new Client(apiKey: 'sk_test_x', httpClient: new RecordingClient([]));
        $this->assertSame('live', $live->mode);
        $this->assertSame('test', $test->mode);
    }

    public function test_invalid_key_prefix_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client(apiKey: 'nope');
    }

    public function test_send_returns_result_and_auto_idempotency(): void
    {
        $http = new RecordingClient([new Response(202, [], '{"id":"msg_1","status":"queued","livemode":false}')]);
        $res = $this->client($http)->send(new TemplateSend(template: 'welcome', to: 'a@b.com'));
        $this->assertInstanceOf(SendResult::class, $res);
        $this->assertSame('msg_1', $res->id);
        $this->assertNotEmpty($http->requests[0]->getHeaderLine('Idempotency-Key')); // auto-generated
    }

    public function test_send_uses_explicit_idempotency_key(): void
    {
        $http = new RecordingClient([new Response(202, [], '{"id":"msg_1","status":"queued","livemode":false}')]);
        $this->client($http)->send(new TemplateSend(template: 'welcome', to: 'a@b.com', idempotencyKey: 'k1'));
        $this->assertSame('k1', $http->requests[0]->getHeaderLine('Idempotency-Key'));
    }

    public function test_send_raw(): void
    {
        $http = new RecordingClient([new Response(202, [], '{"id":"msg_2","status":"queued","livemode":false}')]);
        $res = $this->client($http)->sendRaw(new RawSend(to: '+100', content: new SmsContent('hi')));
        $this->assertSame('msg_2', $res->id);
        $body = json_decode((string) $http->requests[0]->getBody(), true);
        $this->assertSame('sms', $body['channel']);
    }

    public function test_context(): void
    {
        $http = new RecordingClient([new Response(200, [], '{"workspace":{"id":"ws","slug":"acme","name":"Acme"},"mode":"test"}')]);
        $ctx = $this->client($http)->context();
        $this->assertInstanceOf(Context::class, $ctx);
        $this->assertSame('acme', $ctx->workspace->slug);
    }

    public function test_send_batch_continue_on_error_and_indexed_idempotency(): void
    {
        $http = new RecordingClient([
            new Response(202, [], '{"id":"msg_a","status":"queued","livemode":false}'),
            new Response(422, [], '{"error":{"message":"bad"}}'),
        ]);
        $results = $this->client($http)->sendBatch(
            [new TemplateSend(template: 'welcome', to: 'a@b.com'), new TemplateSend(template: 'welcome', to: 'bad')],
            new BatchOptions(idempotencyKey: 'batch'),
        );
        $this->assertTrue($results[0]->ok);
        $this->assertSame('msg_a', $results[0]->result->id);
        $this->assertFalse($results[1]->ok);
        $this->assertSame('batch-0', $http->requests[0]->getHeaderLine('Idempotency-Key'));
        $this->assertSame('batch-1', $http->requests[1]->getHeaderLine('Idempotency-Key'));
    }

    public function test_send_batch_uses_request_idempotency_key_when_no_options(): void
    {
        $http = new RecordingClient([
            new Response(202, [], '{"id":"msg_a","status":"queued","livemode":false}'),
        ]);
        $results = $this->client($http)->sendBatch([
            new TemplateSend(template: 'welcome', to: 'a@b.com', idempotencyKey: 'mine-1'),
        ]);
        $this->assertTrue($results[0]->ok);
        $this->assertSame('mine-1', $http->requests[0]->getHeaderLine('Idempotency-Key'));
    }
}
