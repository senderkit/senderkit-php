<?php

declare(strict_types=1);

namespace SenderKit\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SenderKit\Exception\ApiException;
use SenderKit\Exception\AuthenticationException;
use SenderKit\Exception\NetworkException;
use SenderKit\Exception\RateLimitException;
use SenderKit\Exception\SenderKitException;
use SenderKit\Exception\TimeoutException;
use SenderKit\Exception\ValidationException;
use SenderKit\Version;

class Transport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeoutMs,
        private readonly int $maxRetries,
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param array<string,string>|null $query
     * @param array<string,mixed>|null  $body
     * @return array<string,mixed>
     */
    public function request(
        string $method,
        string $path,
        ?array $query = null,
        ?array $body = null,
        ?string $idempotencyKey = null,
    ): array {
        $response = $this->send($method, $path, $query, $body, $idempotencyKey);
        return $this->decode($response);
    }

    /**
     * @param array<string,string>|null $query
     * @param array<string,mixed>|null  $body
     */
    protected function send(string $method, string $path, ?array $query, ?array $body, ?string $idempotencyKey): ResponseInterface
    {
        $uri = rtrim($this->baseUrl, '/') . $path;
        if ($query) {
            $uri .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'senderkit-php/' . Version::VALUE);

        if ($body !== null) {
            try {
                $json = json_encode($body, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ApiException('Failed to encode request body as JSON.', 0);
            }
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($json));
        }
        if ($idempotencyKey !== null) {
            $request = $request->withHeader('Idempotency-Key', $idempotencyKey);
        }

        $attempt = 0;
        while (true) {
            $stream = $request->getBody();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            try {
                $response = $this->client->sendRequest($request);
            } catch (NetworkExceptionInterface $e) {
                if (stripos($e->getMessage(), 'timed out') !== false || stripos($e->getMessage(), 'timeout') !== false) {
                    throw new TimeoutException('Request timed out after ' . $this->timeoutMs . 'ms');
                }
                throw new NetworkException($e->getMessage(), $e);  // Delta A: 2-arg form
            } catch (ClientExceptionInterface $e) {
                throw new NetworkException($e->getMessage(), $e);  // Delta A: 2-arg form
            }

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return $response;
            }

            if ($this->isRetryable($status) && $attempt < $this->maxRetries) {
                $this->sleepMs($this->backoffMs($attempt, $response));
                $attempt++;
                continue;
            }

            throw $this->toException($response);
        }
    }

    protected function isRetryable(int $status): bool
    {
        return $status === 429 || ($status >= 500 && $status !== 501);
    }

    protected function backoffMs(int $attempt, ResponseInterface $response): int
    {
        $retryAfter = $this->retryAfterMs($response);
        if ($retryAfter !== null) {
            return $retryAfter;
        }
        $ceiling = min(250 * (2 ** $attempt), 5000);
        return random_int(0, (int) $ceiling);
    }

    protected function sleepMs(int $ms): void
    {
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    /** @return array<string,mixed> */
    protected function decode(ResponseInterface $response): array
    {
        $raw = (string) $response->getBody();
        if ($raw === '') {
            return [];
        }
        try {
            /** @var array<string,mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ApiException('Failed to parse API response as JSON.', $response->getStatusCode());
        }

        return $decoded;
    }

    protected function toException(ResponseInterface $response): SenderKitException
    {
        $status = $response->getStatusCode();
        $requestId = $response->getHeaderLine('x-request-id') ?: null;

        $body = json_decode((string) $response->getBody(), true);
        $err = is_array($body) ? ($body['error'] ?? $body) : [];
        $code = is_array($err) && isset($err['code']) && is_scalar($err['code']) ? (string) $err['code'] : null;
        $message = is_array($err) && isset($err['message']) && is_scalar($err['message']) ? (string) $err['message'] : "HTTP {$status}";
        /** @var array<string,mixed>|list<mixed>|null $issues */
        $issues = is_array($err) && isset($err['issues']) && is_array($err['issues']) ? $err['issues'] : null;

        return match (true) {
            $status === 401, $status === 403 => new AuthenticationException($message, $status, $code, $issues, $requestId),
            $status === 400, $status === 422 => new ValidationException($message, $status, $code, $issues, $requestId),
            $status === 429 => new RateLimitException(
                $message,
                $status,
                $code,
                $issues,
                $requestId,
                retryAfterMs: $this->retryAfterMs($response),
            ),
            default => new ApiException($message, $status, $code, $issues, $requestId),
        };
    }

    protected function retryAfterMs(ResponseInterface $response): ?int
    {
        $header = $response->getHeaderLine('Retry-After');
        return $header !== '' && is_numeric($header) ? (int) ((float) $header * 1000) : null;
    }
}
