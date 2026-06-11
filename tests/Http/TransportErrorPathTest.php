<?php

declare(strict_types=1);

namespace SenderKit\Tests\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use SenderKit\Exception\NetworkException;
use SenderKit\Exception\TimeoutException;
use SenderKit\Http\Transport;
use SenderKit\Tests\Support\FakeNetworkException;
use SenderKit\Tests\Support\ThrowingClient;

final class TransportErrorPathTest extends TestCase
{
    private function transport(ThrowingClient $client): Transport
    {
        $f = new Psr17Factory();
        return new Transport('sk_test_abc', 'https://api.senderkit.com', 30000, 0, $client, $f, $f);
    }

    private function dummyRequest(): \Psr\Http\Message\RequestInterface
    {
        return (new Psr17Factory())->createRequest('GET', 'https://api.senderkit.com/v1/context');
    }

    public function test_timeout_message_maps_to_timeout_exception(): void
    {
        $client = new ThrowingClient(new FakeNetworkException('cURL error 28: Operation timed out', $this->dummyRequest()));
        $t = $this->transport($client);
        $this->expectException(TimeoutException::class);
        $t->request('GET', '/v1/context');
    }

    public function test_generic_network_error_maps_to_network_exception(): void
    {
        $client = new ThrowingClient(new FakeNetworkException('Connection refused', $this->dummyRequest()));
        $t = $this->transport($client);
        $this->expectException(NetworkException::class);
        $t->request('GET', '/v1/context');
    }

    public function test_client_exception_maps_to_network_exception(): void
    {
        $fakeClientException = new class ('Something went wrong') extends \RuntimeException implements ClientExceptionInterface {};
        $client = new ThrowingClient($fakeClientException);
        $t = $this->transport($client);
        $this->expectException(NetworkException::class);
        $t->request('GET', '/v1/context');
    }
}
