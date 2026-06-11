<?php

declare(strict_types=1);

namespace SenderKit\Resource;

use SenderKit\Http\Transport;
use SenderKit\Request\Serialize;
use SenderKit\Response\RenderResult;
use SenderKit\Response\Template;

final class Templates
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @return list<Template> */
    public function list(): array
    {
        $data = $this->transport->request('GET', '/v1/templates');
        $rows = is_array($data['data'] ?? null) ? $data['data'] : [];
        /** @var list<array<string,mixed>> $rows */
        return array_map(
            static fn (array $t) => Template::fromArray($t),
            $rows,
        );
    }

    public function get(string $slug): Template
    {
        return Template::fromArray($this->transport->request('GET', '/v1/templates/' . rawurlencode($slug)));
    }

    /** @param array<string,mixed> $vars */
    public function render(string $slug, array $vars = []): RenderResult
    {
        return RenderResult::fromArray($this->transport->request(
            'POST',
            '/v1/templates/' . rawurlencode($slug) . '/render',
            body: ['vars' => Serialize::record($vars)],
        ));
    }
}
