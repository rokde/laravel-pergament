<?php

declare(strict_types=1);

use Pergament\Support\PortableLinkRewriter;

function rewriter(string $base = '', string $docs = 'docs', string $blog = 'blog'): PortableLinkRewriter
{
    return new PortableLinkRewriter(['localhost', 'example.com'], $base, $docs, $blog);
}

it('maps a root-relative route to a flat .html file', function (): void {
    expect(rewriter()->resolve('/docs/getting-started/introduction', 'index.html'))
        ->toBe('docs/getting-started/introduction.html');
});

it('maps section index routes to index.html', function (): void {
    expect(rewriter()->resolve('/docs', 'index.html'))->toBe('docs/index.html');
    expect(rewriter()->resolve('/blog', 'index.html'))->toBe('blog/index.html');
    expect(rewriter()->resolve('/', 'about.html'))->toBe('index.html');
});

it('resolves sibling links relatively without redundant prefix', function (): void {
    expect(rewriter()->resolve('/docs/getting-started/configuration', 'docs/getting-started/introduction.html'))
        ->toBe('configuration.html');
});

it('walks up directories for cross-chapter links', function (): void {
    expect(rewriter()->resolve('/docs/advanced/customization', 'docs/getting-started/introduction.html'))
        ->toBe('../advanced/customization.html');
});

it('rewrites bundled assets and strips version queries', function (): void {
    expect(rewriter()->resolve('http://localhost/vendor/pergament/pergament.css?v=abc123', 'index.html'))
        ->toBe('assets/pergament.css');

    expect(rewriter()->resolve('http://localhost/vendor/pergament/pergament.css', 'docs/getting-started/introduction.html'))
        ->toBe('../../assets/pergament.css');
});

it('maps the feed route to feed.xml', function (): void {
    expect(rewriter()->resolve('/blog/feed', 'index.html'))->toBe('blog/feed.xml');
});

it('keeps media files with their extension', function (): void {
    expect(rewriter()->resolve('/docs/media/getting-started/document.txt', 'docs/getting-started/introduction.html'))
        ->toBe('../media/getting-started/document.txt');
});

it('converts pagination query strings into page paths', function (): void {
    expect(rewriter()->resolve('/blog?page=2', 'blog/index.html'))->toBe('page/2.html');
    expect(rewriter()->resolve('http://localhost/blog?page=3', 'blog/page/2.html'))->toBe('3.html');
});

it('leaves external links untouched', function (): void {
    expect(rewriter()->resolve('https://github.com/rokde/laravel-pergament', 'index.html'))
        ->toBe('https://github.com/rokde/laravel-pergament');
    expect(rewriter()->resolve('//cdn.example.org/x.js', 'index.html'))->toBe('//cdn.example.org/x.js');
});

it('leaves anchors, mailto and tel untouched', function (): void {
    expect(rewriter()->resolve('#section', 'index.html'))->toBe('#section');
    expect(rewriter()->resolve('mailto:hi@example.com', 'index.html'))->toBe('mailto:hi@example.com');
    expect(rewriter()->resolve('tel:+1234', 'index.html'))->toBe('tel:+1234');
});

it('preserves fragments on internal links', function (): void {
    expect(rewriter()->resolve('/docs/getting-started/introduction#install', 'index.html'))
        ->toBe('docs/getting-started/introduction.html#install');
});

it('honours a global base prefix', function (): void {
    expect(rewriter('app')->resolve('/app/docs/intro/start', 'index.html'))
        ->toBe('docs/intro/start.html');
    expect(rewriter('app')->resolve('/app', 'index.html'))->toBe('index.html');
});

it('rewrites href and src attributes in html', function (): void {
    $html = '<a href="/docs/getting-started/configuration">cfg</a><img src="http://localhost/vendor/pergament/x.png?v=1">';
    $out = rewriter()->rewriteHtml($html, 'docs/getting-started/introduction.html');

    expect($out)->toContain('href="configuration.html"')
        ->and($out)->toContain('src="../../assets/x.png"');
});

it('rewrites markdown links to .md targets', function (): void {
    $md = 'See [config](/docs/getting-started/configuration) and ![pic](/docs/media/getting-started/x.png).';
    $out = rewriter()->rewriteMarkdown($md, 'docs/getting-started/introduction.md');

    expect($out)->toContain('[config](configuration.md)')
        ->and($out)->toContain('![pic](../media/getting-started/x.png)');
});
