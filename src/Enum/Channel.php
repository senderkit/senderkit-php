<?php

declare(strict_types=1);

namespace SenderKit\Enum;

enum Channel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Push = 'push';
    case WebPush = 'web-push';
}
