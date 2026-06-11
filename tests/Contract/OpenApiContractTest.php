<?php

declare(strict_types=1);

namespace SenderKit\Tests\Contract;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class OpenApiContractTest extends TestCase
{
    /** @var array<string,mixed> */
    private static array $spec = [];

    public static function setUpBeforeClass(): void
    {
        $path = getenv('SENDERKIT_OPENAPI')
            ?: __DIR__ . '/../../../../../senderkit-app/public/openapi.yaml';
        if (!is_file($path)) {
            self::markTestSkipped("OpenAPI spec not found at {$path}");
        }
        /** @var array<string,mixed> $parsed */
        $parsed = Yaml::parseFile($path);
        self::$spec = $parsed;
    }

    /**
     * @return iterable<string,array{string,string}>
     */
    public static function endpointProvider(): iterable
    {
        yield 'send'             => ['post', '/v1/send'];
        yield 'context'          => ['get', '/v1/context'];
        yield 'messages.list'    => ['get', '/v1/messages'];
        yield 'messages.get'     => ['get', '/v1/messages/{id}'];
        yield 'messages.cancel'  => ['delete', '/v1/messages/{id}'];
        yield 'templates.list'   => ['get', '/v1/templates'];
        yield 'templates.get'    => ['get', '/v1/templates/{slug}'];
        yield 'templates.render' => ['post', '/v1/templates/{slug}/render'];
    }

    /** @dataProvider endpointProvider */
    public function test_endpoint_exists_in_spec(string $method, string $path): void
    {
        $paths = self::$spec['paths'] ?? [];
        $this->assertArrayHasKey($path, $paths, "Spec missing path {$path}");
        $this->assertArrayHasKey($method, $paths[$path], "Spec {$path} missing {$method}");
    }

    public function test_base_url_matches_default(): void
    {
        $servers = self::$spec['servers'] ?? [];
        $urls = array_column($servers, 'url');
        $this->assertContains('https://api.senderkit.com', $urls);
    }
}
