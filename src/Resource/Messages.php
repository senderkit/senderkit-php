<?php

declare(strict_types=1);

namespace SenderKit\Resource;

use SenderKit\Http\Transport;
use SenderKit\Request\ListMessagesParams;
use SenderKit\Response\CancelResult;
use SenderKit\Response\Message;
use SenderKit\Response\MessageList;

final class Messages
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function list(?ListMessagesParams $params = null): MessageList
    {
        return MessageList::fromArray($this->transport->request(
            'GET',
            '/v1/messages',
            query: $params?->toQuery() ?: null,
        ));
    }

    public function get(string $id): Message
    {
        return Message::fromArray($this->transport->request('GET', '/v1/messages/' . rawurlencode($id)));
    }

    public function cancel(string $id): CancelResult
    {
        return CancelResult::fromArray($this->transport->request('DELETE', '/v1/messages/' . rawurlencode($id)));
    }
}
