<?php

declare(strict_types=1);

namespace SenderKit\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class HttpClientFactory
{
    public function client(int $timeoutMs): ClientInterface
    {
        if (class_exists(\GuzzleHttp\Client::class)) {
            /** @var ClientInterface */
            return new \GuzzleHttp\Client([
                'timeout' => $timeoutMs / 1000,
                'connect_timeout' => $timeoutMs / 1000,
                'http_errors' => false,
            ]);
        }

        // PSR-18 has no standard timeout API; install guzzlehttp/guzzle for timeout support.
        return Psr18ClientDiscovery::find();
    }

    public function requestFactory(): RequestFactoryInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory();
    }

    public function streamFactory(): StreamFactoryInterface
    {
        return Psr17FactoryDiscovery::findStreamFactory();
    }
}
