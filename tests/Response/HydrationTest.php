<?php

declare(strict_types=1);

namespace SenderKit\Tests\Response;

use PHPUnit\Framework\TestCase;
use SenderKit\Enum\Channel;
use SenderKit\Response\{SendResult, Message, MessageList, Context, CancelResult, RenderResult};

final class HydrationTest extends TestCase
{
    public function test_send_result(): void
    {
        $r = SendResult::fromArray(['id' => 'msg_1', 'status' => 'queued', 'livemode' => true]);
        $this->assertSame('msg_1', $r->id);
        $this->assertSame('queued', $r->status);
        $this->assertTrue($r->livemode);
    }

    public function test_message_and_list(): void
    {
        $list = MessageList::fromArray([
            'data' => [[
                'id' => 'int_1', 'publicId' => 'msg_1', 'status' => 'delivered',
                'channel' => 'email', 'templateSlug' => 'welcome',
                'recipient' => 'a@b.com', 'createdAt' => '2030-01-01T00:00:00Z',
            ]],
            'nextCursor' => 'cur_2',
        ]);
        $this->assertCount(1, $list->data);
        $this->assertSame('cur_2', $list->nextCursor);
        $this->assertInstanceOf(Message::class, $list->data[0]);
        $this->assertSame(Channel::Email, $list->data[0]->channel);
        $this->assertSame('msg_1', $list->data[0]->publicId);
    }

    public function test_context(): void
    {
        $c = Context::fromArray([
            'workspace' => ['id' => 'ws_1', 'slug' => 'acme', 'name' => 'Acme'],
            'mode' => 'test',
        ]);
        $this->assertSame('acme', $c->workspace->slug);
        $this->assertSame('test', $c->mode);
    }

    public function test_render_result(): void
    {
        $r = RenderResult::fromArray([
            'channel' => 'email',
            'output' => ['subject' => 'Hi', 'html' => '<p>x</p>'],
            'missing' => ['name'],
        ]);
        $this->assertSame(Channel::Email, $r->channel);
        $this->assertSame(['subject' => 'Hi', 'html' => '<p>x</p>'], $r->output);
        $this->assertSame(['name'], $r->missing);
    }

    public function test_cancel_result(): void
    {
        $r = CancelResult::fromArray(['id' => 'msg_1', 'status' => 'canceled']);
        $this->assertSame('canceled', $r->status);
    }

    public function test_unknown_channel_throws_senderkit_exception(): void
    {
        $this->expectException(\SenderKit\Exception\SenderKitException::class);
        \SenderKit\Response\Message::fromArray([
            'id' => 'i', 'publicId' => 'msg_1', 'status' => 'queued',
            'channel' => 'carrier-pigeon', 'templateSlug' => null,
            'recipient' => 'a@b.com', 'createdAt' => '2030-01-01T00:00:00Z',
        ]);
    }
}
