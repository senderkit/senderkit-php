<?php

declare(strict_types=1);

namespace SenderKit\Tests\Request;

use PHPUnit\Framework\TestCase;
use SenderKit\Enum\Channel;
use SenderKit\Request\{Attachment, EmailContent, SmsContent, PushContent, WebPushContent};

final class ContentTest extends TestCase
{
    public function test_email_content_omits_nulls_and_serializes_attachments(): void
    {
        $c = new EmailContent(
            subject: 'Hi',
            html: '<p>x</p>',
            attachments: [new Attachment('r.pdf', 'application/pdf', 'YmFzZTY0')],
        );
        $this->assertSame(Channel::Email, $c->channel());
        $this->assertSame([
            'subject' => 'Hi',
            'html' => '<p>x</p>',
            'attachments' => [
                ['filename' => 'r.pdf', 'contentType' => 'application/pdf', 'content' => 'YmFzZTY0'],
            ],
        ], $c->toArray());
    }

    public function test_sms_content(): void
    {
        $this->assertSame(Channel::Sms, (new SmsContent('hello'))->channel());
        $this->assertSame(['body' => 'hello'], (new SmsContent('hello'))->toArray());
    }

    public function test_push_and_webpush_channels(): void
    {
        $this->assertSame(Channel::Push, (new PushContent('t', 'b'))->channel());
        $this->assertSame(Channel::WebPush, (new WebPushContent('t', 'b'))->channel());
    }

    public function test_push_content_preserves_falsy_badge_zero(): void
    {
        $arr = (new PushContent('t', 'b', badge: 0))->toArray();
        $this->assertTrue(array_key_exists('badge', $arr));
        $this->assertSame(0, $arr['badge']);
    }

    public function test_push_empty_data_encodes_as_json_object(): void
    {
        $json = json_encode((new PushContent('t', 'b', data: []))->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"data":{}', $json);

        $json = json_encode((new WebPushContent('t', 'b', data: []))->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"data":{}', $json);
    }

    public function test_attachment_preserves_falsy_inline_false(): void
    {
        $arr = (new Attachment('f', 'text/plain', 'x', inline: false))->toArray();
        $this->assertTrue(array_key_exists('inline', $arr));
        $this->assertFalse($arr['inline']);
    }
}
