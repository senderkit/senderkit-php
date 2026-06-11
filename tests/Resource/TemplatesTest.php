<?php

declare(strict_types=1);

namespace SenderKit\Tests\Resource;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SenderKit\Http\Transport;
use SenderKit\Resource\Templates;
use SenderKit\Response\RenderResult;
use SenderKit\Response\Template;
use SenderKit\Tests\Support\RecordingClient;

final class TemplatesTest extends TestCase
{
    private function templates(RecordingClient $client): Templates
    {
        $f = new Psr17Factory();
        return new Templates(new Transport('sk_test_x', 'https://api.senderkit.com', 30000, 0, $client, $f, $f));
    }

    public function test_list_unwraps_data(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"data":[{"slug":"welcome","channel":"email","description":null,"status":"published","updatedAt":"2030-01-01T00:00:00Z"}]}')]);
        $templates = $this->templates($client)->list();
        $this->assertCount(1, $templates);
        $this->assertInstanceOf(Template::class, $templates[0]);
        $this->assertSame('welcome', $templates[0]->slug);
    }

    public function test_get_includes_current_version(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"slug":"welcome","channel":"email","description":"d","status":"published","updatedAt":"2030-01-01T00:00:00Z","currentVersion":{"versionNumber":3,"variables":{},"publishedAt":"2030-01-01T00:00:00Z"}}')]);
        $tpl = $this->templates($client)->get('welcome');
        $this->assertSame(3, $tpl->currentVersion?->versionNumber);
    }

    public function test_render_with_empty_vars_sends_json_object(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"channel":"email","output":{},"missing":[]}')]);
        $this->templates($client)->render('welcome');
        $this->assertSame('{"vars":{}}', (string) $client->requests[0]->getBody());
    }

    public function test_render_posts_vars(): void
    {
        $client = new RecordingClient([new Response(200, [], '{"channel":"email","output":{"subject":"Hi"},"missing":[]}')]);
        $res = $this->templates($client)->render('welcome', ['name' => 'Ada']);
        $this->assertInstanceOf(RenderResult::class, $res);
        $this->assertSame('/v1/templates/welcome/render', $client->requests[0]->getUri()->getPath());
        $this->assertSame(['vars' => ['name' => 'Ada']], json_decode((string) $client->requests[0]->getBody(), true));
    }
}
