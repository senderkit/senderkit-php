<?php

declare(strict_types=1);

namespace SenderKit\Tests\Resource;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SenderKit\Enum\Channel;
use SenderKit\Http\Transport;
use SenderKit\Request\ListMessagesParams;
use SenderKit\Resource\Messages;
use SenderKit\Response\CancelResult;
use SenderKit\Response\Message;
use SenderKit\Response\MessageList;
use SenderKit\Tests\Support\RecordingClient;

final class MessagesTest extends TestCase
{
    private function messages(RecordingClient $client): Messages
    {
        $f = new Psr17Factory();
        return new Messages(new Transport('sk_test_x', 'https://api.senderkit.com', 30000, 0, $client, $f, $f));
    }

    public function test_list_sends_query_and_hydrates(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"data":[{"id":"i","publicId":"msg_1","status":"queued","channel":"email","templateSlug":null,"recipient":"a@b.com","createdAt":"2030-01-01T00:00:00Z"}],"nextCursor":null}')]);
        $m = $this->messages($client);
        $list = $m->list(new ListMessagesParams(limit: 10, channel: Channel::Email));
        $this->assertInstanceOf(MessageList::class, $list);
        $this->assertSame('msg_1', $list->data[0]->publicId);
        $uri = (string) $client->requests[0]->getUri();
        $this->assertStringContainsString('/v1/messages?', $uri);
        $this->assertStringContainsString('channel=email', $uri);
    }

    public function test_get(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"id":"i","publicId":"msg_1","status":"delivered","channel":"sms","templateSlug":null,"recipient":"+100","createdAt":"2030-01-01T00:00:00Z"}')]);
        $msg = $this->messages($client)->get('msg_1');
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertSame('/v1/messages/msg_1', $client->requests[0]->getUri()->getPath());
    }

    public function test_cancel(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"id":"msg_1","status":"canceled"}')]);
        $res = $this->messages($client)->cancel('msg_1');
        $this->assertInstanceOf(CancelResult::class, $res);
        $this->assertSame('DELETE', $client->requests[0]->getMethod());
    }
}
