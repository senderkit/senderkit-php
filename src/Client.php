<?php

declare(strict_types=1);

namespace SenderKit;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SenderKit\Exception\SenderKitException;
use SenderKit\Http\HttpClientFactory;
use SenderKit\Http\Idempotency;
use SenderKit\Http\Transport;
use SenderKit\Request\BatchOptions;
use SenderKit\Request\RawSend;
use SenderKit\Request\TemplateSend;
use SenderKit\Resource\Messages;
use SenderKit\Resource\Templates;
use SenderKit\Response\BatchResult;
use SenderKit\Response\Context;
use SenderKit\Response\SendResult;

final class Client
{
    public readonly string $mode;

    public readonly Messages $messages;

    public readonly Templates $templates;

    private readonly Transport $transport;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.senderkit.com',
        int $timeoutMs = 30000,
        int $maxRetries = 2,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->mode = match (true) {
            str_starts_with($apiKey, 'sk_live_') => 'live',
            str_starts_with($apiKey, 'sk_test_') => 'test',
            default => throw new \InvalidArgumentException(
                'Invalid API key: expected an sk_live_ or sk_test_ prefix.',
            ),
        };

        if ($httpClient === null || $requestFactory === null || $streamFactory === null) {
            $factory = new HttpClientFactory();
            $httpClient ??= $factory->client($timeoutMs);
            $requestFactory ??= $factory->requestFactory();
            $streamFactory ??= $factory->streamFactory();
        }

        $this->transport = new Transport(
            $apiKey,
            $baseUrl,
            $timeoutMs,
            $maxRetries,
            $httpClient,
            $requestFactory,
            $streamFactory,
        );

        $this->messages = new Messages($this->transport);
        $this->templates = new Templates($this->transport);
    }

    public function send(TemplateSend $request): SendResult
    {
        return $this->dispatch($request->toArray(), $request->idempotencyKey);
    }

    public function sendRaw(RawSend $request): SendResult
    {
        return $this->dispatch($request->toArray(), $request->idempotencyKey);
    }

    /**
     * @param list<TemplateSend|RawSend> $requests
     * @return list<BatchResult>
     */
    public function sendBatch(array $requests, ?BatchOptions $options = null): array
    {
        $results = [];
        foreach ($requests as $index => $request) {
            $idempotencyKey = $options?->idempotencyKey !== null
                ? $options->idempotencyKey . '-' . $index
                : $request->idempotencyKey;

            try {
                $result = $this->dispatch($request->toArray(), $idempotencyKey);
                $results[] = BatchResult::success($index, $result);
            } catch (SenderKitException $e) {
                $results[] = BatchResult::failure($index, $e);
            }
        }

        return $results;
    }

    /** @param array<string,mixed> $body */
    private function dispatch(array $body, ?string $idempotencyKey): SendResult
    {
        return SendResult::fromArray($this->transport->request(
            'POST',
            '/v1/send',
            body: $body,
            idempotencyKey: $idempotencyKey ?? Idempotency::uuidv4(),
        ));
    }

    public function context(): Context
    {
        return Context::fromArray($this->transport->request('GET', '/v1/context'));
    }
}
