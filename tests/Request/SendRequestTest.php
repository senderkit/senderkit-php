<?php

declare(strict_types=1);

namespace SenderKit\Tests\Request;

use PHPUnit\Framework\TestCase;
use SenderKit\Enum\Channel;
use SenderKit\Request\{TemplateSend, RawSend, EmailContent, SmsContent, ListMessagesParams};

final class SendRequestTest extends TestCase
{
    public function test_template_send_body_omits_nulls(): void
    {
        $req = new TemplateSend(
            template: 'welcome',
            to: 'a@b.com',
            vars: ['name' => 'Ada'],
            metadata: ['userId' => 'u1'],
            idempotencyKey: 'k1',
        );
        $this->assertSame([
            'template' => 'welcome',
            'to' => 'a@b.com',
            'vars' => ['name' => 'Ada'],
            'metadata' => ['userId' => 'u1'],
        ], $req->toArray());
        $this->assertSame('k1', $req->idempotencyKey);
    }

    public function test_template_send_serializes_scheduled_at(): void
    {
        $req = new TemplateSend(
            template: 'welcome',
            to: 'a@b.com',
            scheduledAt: new \DateTimeImmutable('2030-01-02T03:04:05+00:00'),
        );
        $this->assertSame('2030-01-02T03:04:05+00:00', $req->toArray()['scheduledAt']);
    }

    public function test_raw_send_injects_channel_from_content(): void
    {
        $req = new RawSend(
            to: 'a@b.com',
            content: new EmailContent(subject: 'S', html: '<p>x</p>'),
            metadata: ['source' => 'checkout'],
        );
        $body = $req->toArray();
        $this->assertSame('email', $body['channel']);
        $this->assertSame(['subject' => 'S', 'html' => '<p>x</p>'], $body['content']);
        $this->assertSame(['source' => 'checkout'], $body['metadata']);
    }

    public function test_raw_send_preserves_falsy_interpolate_false(): void
    {
        $arr = (new RawSend(to: '+1', content: new SmsContent('hi'), interpolate: false))->toArray();
        $this->assertTrue(array_key_exists('interpolate', $arr));
        $this->assertFalse($arr['interpolate']);
    }

    public function test_empty_vars_and_metadata_encode_as_json_objects(): void
    {
        $req = new TemplateSend(template: 'welcome', to: 'a@b.com', vars: [], metadata: []);
        $json = json_encode($req->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"vars":{}', $json);
        $this->assertStringContainsString('"metadata":{}', $json);
    }

    public function test_raw_send_empty_vars_encode_as_json_object(): void
    {
        $req = new RawSend(to: '+1', content: new SmsContent('hi'), vars: [], metadata: []);
        $json = json_encode($req->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"vars":{}', $json);
        $this->assertStringContainsString('"metadata":{}', $json);
    }

    public function test_list_params_query_with_metadata_filter(): void
    {
        $params = new ListMessagesParams(
            limit: 50,
            status: 'queued',
            channel: Channel::Email,
            template: 'welcome',
            metadata: ['userId' => 'u1'],
        );
        $this->assertSame([
            'limit' => '50',
            'status' => 'queued',
            'channel' => 'email',
            'template' => 'welcome',
            'metadata[userId]' => 'u1',
        ], $params->toQuery());
    }
}
