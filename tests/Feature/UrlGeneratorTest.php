<?php

declare(strict_types=1);

use Pergament\Support\UrlGenerator;

it('builds paths with default root prefix', function (): void {
    config()->set('pergament.prefix', '/');

    expect(UrlGenerator::path('docs', 'chapter', 'page'))->toBe('/docs/chapter/page');
    expect(UrlGenerator::path('blog', 'my-post'))->toBe('/blog/my-post');
    expect(UrlGenerator::path())->toBe('/');
});

it('builds paths with custom prefix', function (): void {
    config()->set('pergament.prefix', 'landing-page');

    expect(UrlGenerator::path('docs', 'chapter', 'page'))->toBe('/landing-page/docs/chapter/page');
    expect(UrlGenerator::path('blog', 'my-post'))->toBe('/landing-page/blog/my-post');
    expect(UrlGenerator::path())->toBe('/landing-page');
});

it('builds full urls with site url and prefix', function (): void {
    config()->set('pergament.prefix', 'docs');
    config()->set('pergament.site.url', 'https://example.com');

    expect(UrlGenerator::url('getting-started', 'intro'))->toBe('https://example.com/docs/getting-started/intro');
    expect(UrlGenerator::url())->toBe('https://example.com/docs');
});

it('builds full urls with root prefix', function (): void {
    config()->set('pergament.prefix', '/');
    config()->set('pergament.site.url', 'https://example.com');

    expect(UrlGenerator::url('blog', 'my-post'))->toBe('https://example.com/blog/my-post');
    expect(UrlGenerator::url())->toBe('https://example.com/');
});

it('strips trailing slash from site url', function (): void {
    config()->set('pergament.prefix', '/');
    config()->set('pergament.site.url', 'https://example.com/');

    expect(UrlGenerator::url('blog'))->toBe('https://example.com/blog');
});

it('returns docs prefix combined with base prefix', function (): void {
    config()->set('pergament.prefix', 'my-site');
    config()->set('pergament.docs.url_prefix', 'documentation');

    expect(UrlGenerator::docsPrefix())->toBe('my-site/documentation');
});

it('returns blog prefix combined with base prefix', function (): void {
    config()->set('pergament.prefix', '/');
    config()->set('pergament.blog.url_prefix', 'articles');

    expect(UrlGenerator::blogPrefix())->toBe('articles');
});
