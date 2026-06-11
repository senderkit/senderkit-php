<?php

declare(strict_types=1);

namespace SenderKit\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ThrowingClient implements ClientInterface
{
    public function __construct(private \Throwable $error)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw $this->error;
    }
}
