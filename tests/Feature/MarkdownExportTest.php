<?php

declare(strict_types=1);

use Pergament\Services\BlogService;
use Pergament\Services\DocumentationService;
use Pergament\Services\PageService;
use Pergament\Support\MarkdownExporter;

it('strips style, script and raw html tags when serving a page as markdown', function (): void {
    $response = $this->get('/html-page.md');

    $response->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');

    $content = $response->getContent();

    expect($content)->toContain('# HTML Page')
        ->and($content)->toContain('Styled content')
        ->and($content)->toContain('Plain paragraph text.')
        ->and($content)->not->toContain('<style>')
        ->and($content)->not->toContain('.custom { color: red; }')
        ->and($content)->not->toContain('<script>')
        ->and($content)->not->toContain("console.log('tracker')")
        ->and($content)->not->toContain('<section');
});

it('strips raw html when the homepage is served as markdown', function (): void {
    config()->set('pergament.homepage.source', 'html-page');

    $content = $this->get('/index.md')->assertStatus(200)->getContent();

    expect($content)->toContain('# HTML Page')
        ->and($content)->toContain('Styled content')
        ->and($content)->not->toContain('<script>')
        ->and($content)->not->toContain('<style>')
        ->and($content)->not->toContain('<section');
});

it('serves a page as markdown identical to the shared exporter output', function (): void {
    $page = app(PageService::class)->getRenderedPage('about');
    $expected = app(MarkdownExporter::class)->fromHtml($page['htmlContent'], $page['title']);

    $response = $this->get('/about.md');

    $response->assertStatus(200);
    expect($response->getContent())->toBe($expected);
});

it('serves a blog post as markdown identical to the shared exporter output', function (): void {
    $post = app(BlogService::class)->getRenderedPost('hello-world');
    $expected = app(MarkdownExporter::class)->fromHtml($post['htmlContent'], $post['title']);

    $response = $this->get('/blog/hello-world.md');

    $response->assertStatus(200);
    expect($response->getContent())->toBe($expected);
});

it('serves a doc page as markdown identical to the shared exporter output', function (): void {
    $page = app(DocumentationService::class)->getRenderedPage('getting-started', 'introduction');
    $expected = app(MarkdownExporter::class)->fromHtml($page['htmlContent'], $page['title']);

    $response = $this->get('/docs/getting-started/introduction.md');

    $response->assertStatus(200);
    expect($response->getContent())->toBe($expected);
});

it('exporter removes script and style blocks but keeps inner prose', function (): void {
    $html = '<h2>Heading</h2><style>.a{color:red}</style><script>alert(1)</script><p>Keep me</p>';

    $markdown = app(MarkdownExporter::class)->fromHtml($html, 'Doc Title');

    expect($markdown)->toContain('# Doc Title')
        ->and($markdown)->toContain('Keep me')
        ->and($markdown)->not->toContain('<style>')
        ->and($markdown)->not->toContain('.a{color:red}')
        ->and($markdown)->not->toContain('<script>')
        ->and($markdown)->not->toContain('alert(1)');
});
