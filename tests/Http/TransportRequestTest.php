<?php

declare(strict_types=1);

namespace SenderKit\Tests\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SenderKit\Exception\ApiException;
use SenderKit\Exception\AuthenticationException;
use SenderKit\Exception\ValidationException;
use SenderKit\Http\Transport;
use SenderKit\Tests\Support\RecordingClient;

final class TransportRequestTest extends TestCase
{
    private function transport(RecordingClient $client): Transport
    {
        $f = new Psr17Factory();
        return new Transport('sk_test_abc', 'https://api.senderkit.com', 30000, 0, $client, $f, $f);
    }

    public function test_post_sets_headers_body_and_idempotency(): void
    {
        $client = new RecordingClient([new Response(202, [], '{"id":"msg_1","status":"queued","livemode":false}')]);
        $t = $this->transport($client);

        $data = $t->request('POST', '/v1/send', body: ['template' => 'welcome', 'to' => 'a@b.com'], idempotencyKey: 'k1');

        $req = $client->requests[0];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('https://api.senderkit.com/v1/send', (string) $req->getUri());
        $this->assertSame('Bearer sk_test_abc', $req->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $req->getHeaderLine('Content-Type'));
        $this->assertSame('k1', $req->getHeaderLine('Idempotency-Key'));
        $this->assertStringContainsString('senderkit-php/', $req->getHeaderLine('User-Agent'));
        $this->assertSame(['template' => 'welcome', 'to' => 'a@b.com'], json_decode((string) $req->getBody(), true));
        $this->assertSame(['id' => 'msg_1', 'status' => 'queued', 'livemode' => false], $data);
    }

    public function test_get_encodes_query(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"data":[]}')]);
        $t = $this->transport($client);
        $t->request('GET', '/v1/messages', query: ['limit' => '50', 'metadata[userId]' => 'u1']);
        $uri = (string) $client->requests[0]->getUri();
        $this->assertStringContainsString('limit=50', $uri);
        $this->assertStringContainsString('metadata%5BuserId%5D=u1', $uri);
    }

    public function test_maps_401_to_authentication_error(): void
    {
        $client = new RecordingClient([new Response(401, ['x-request-id' => 'req_9'], '{"error":{"code":"unauthorized","message":"bad key"}}')]);
        $t = $this->transport($client);
        try {
            $t->request('GET', '/v1/context');
            $this->fail('expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->status);
            $this->assertSame('unauthorized', $e->apiCode);  // Delta B
            $this->assertSame('bad key', $e->getMessage());
            $this->assertSame('req_9', $e->requestId);
        }
    }

    public function test_maps_422_to_validation_error_with_issues(): void
    {
        $client = new RecordingClient([new Response(422, [], '{"error":{"code":"invalid","message":"bad","issues":{"to":"required"}}}')]);
        $t = $this->transport($client);
        try {
            $t->request('POST', '/v1/send', body: []);
            $this->fail('expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['to' => 'required'], $e->issues);
        }
    }

    public function test_maps_flat_error_shape(): void
    {
        $client = new RecordingClient([new Response(404, [], '{"code":"not_found","message":"nope"}')]);
        $t = $this->transport($client);
        try {
            $t->request('GET', '/v1/messages/msg_x');
            $this->fail('expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(404, $e->status);
            $this->assertSame('not_found', $e->apiCode);  // Delta B
        }
    }

    public function test_unencodable_body_throws_api_exception(): void
    {
        $client = new RecordingClient([new Response(202, [], '{}')]);
        $t = $this->transport($client);
        $this->expectException(\SenderKit\Exception\ApiException::class);
        $t->request('POST', '/v1/send', body: ['bad' => NAN]); // NAN is not JSON-encodable
    }
}
