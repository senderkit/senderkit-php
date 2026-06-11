<?php

declare(strict_types=1);

namespace SenderKit\Tests\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SenderKit\Http\HttpClientFactory;

final class HttpClientFactoryTest extends TestCase
{
    public function test_builds_client_and_factories(): void
    {
        $f = new HttpClientFactory();
        $this->assertInstanceOf(ClientInterface::class, $f->client(30000));
        $this->assertInstanceOf(RequestFactoryInterface::class, $f->requestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $f->streamFactory());
    }

    public function test_uses_guzzle_when_available(): void
    {
        if (!class_exists(\GuzzleHttp\Client::class)) {
            $this->markTestSkipped('Guzzle not installed');
        }
        $this->assertInstanceOf(\GuzzleHttp\Client::class, (new HttpClientFactory())->client(5000));
    }
}
