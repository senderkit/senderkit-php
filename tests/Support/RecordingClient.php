<?php

declare(strict_types=1);

namespace SenderKit\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RecordingClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<string> */
    public array $bodies = [];

    /**
     * @param list<ResponseInterface> $responses queued FIFO
     * @param bool $consumeBody when true, reads (and does NOT rewind) the body at send time, simulating a real HTTP client
     */
    public function __construct(private array $responses, private bool $consumeBody = false)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->consumeBody) {
            $this->bodies[] = $request->getBody()->getContents(); // advances stream to EOF, no rewind
        }
        $this->requests[] = $request;
        $response = array_shift($this->responses);
        if ($response === null) {
            throw new \LogicException('No queued response');
        }
        return $response;
    }
}
