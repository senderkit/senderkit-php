<?php

declare(strict_types=1);

namespace SenderKit\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SenderKit\Enum\Channel;

final class ChannelTest extends TestCase
{
    public function test_wire_values(): void
    {
        $this->assertSame('email', Channel::Email->value);
        $this->assertSame('sms', Channel::Sms->value);
        $this->assertSame('push', Channel::Push->value);
        $this->assertSame('web-push', Channel::WebPush->value);
        $this->assertSame(Channel::WebPush, Channel::from('web-push'));
    }
}
