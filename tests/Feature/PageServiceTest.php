<?php

declare(strict_types=1);

use Pergament\Data\Page;
use Pergament\Services\PageService;

it('returns a page by slug', function (): void {
    $service = resolve(PageService::class);
    $page = $service->getPage('home');

    expect($page)->toBeInstanceOf(Page::class);
    expect($page->title)->toBe('Welcome');
    expect($page->excerpt)->toBe('Welcome to our site.');
    expect($page->slug)->toBe('home');
});

it('returns null for non-existent page', function (): void {
    $service = resolve(PageService::class);

    expect($service->getPage('nonexistent'))->toBeNull();
});

it('renders a page with HTML', function (): void {
    $service = resolve(PageService::class);
    $rendered = $service->getRenderedPage('home');

    expect($rendered)->not->toBeNull();
    expect($rendered)->toHaveKeys(['title', 'excerpt', 'htmlContent', 'headings', 'slug', 'layout', 'meta', 'allowHtml', 'statistics', 'styles', 'scripts', 'linkErrors']);
    expect($rendered['htmlContent'])->toContain('This is the homepage content');
});

it('renders raw html in page content', function (): void {
    $slug = 'html-raw-'.uniqid();
    $path = config('pergament.content_path').'/pages/'.$slug.'.md';

    file_put_contents($path, <<<'MARKDOWN'
---
title: HTML Raw
---

# HTML Raw

<section class="custom-hero">
    <button type="button">Click me</button>
</section>
MARKDOWN);

    $service = resolve(PageService::class);
    try {
        $rendered = $service->getRenderedPage($slug);

        expect($rendered)->not->toBeNull();
        expect($rendered['htmlContent'])->toContain('<section class="custom-hero">');
        expect($rendered['htmlContent'])->toContain('<button type="button">Click me</button>');
    } finally {
        unlink($path);
    }
});

it('allows raw html in pages when enabled in front matter', function (): void {
    $slug = 'html-enabled-'.uniqid();
    $path = config('pergament.content_path').'/pages/'.$slug.'.md';

    file_put_contents($path, <<<'MARKDOWN'
---
title: HTML Enabled
allow_html: true
---

# HTML Enabled

<section class="custom-hero">
    <button type="button">Click me</button>
</section>
MARKDOWN);

    $service = resolve(PageService::class);
    try {
        $rendered = $service->getRenderedPage($slug);

        expect($rendered)->not->toBeNull();
        expect($rendered['htmlContent'])->toContain('<section class="custom-hero">');
        expect($rendered['htmlContent'])->toContain('<button type="button">Click me</button>');
        expect($rendered['allowHtml'])->toBeTrue();
    } finally {
        unlink($path);
    }
});

it('preserves layout from front matter', function (): void {
    $service = resolve(PageService::class);
    $page = $service->getPage('home');

    expect($page->layout)->toBe('landing');
});

it('returns an empty collection of pages when the content path does not exists', function (): void {
    config()->set('pergament.content_path', __DIR__.'/fixtures/content_path_does_not_exist');

    $service = resolve(PageService::class);

    expect($service->getSlugs())->toBeEmpty();
});

it('includes sidecar styles and scripts in the rendered page', function (): void {
    $page = app(PageService::class)->getRenderedPage('about');

    expect($page['styles'])->toBe('.about-hero { color: rebeccapurple; }')
        ->and($page['scripts'])->toBe("console.log('about page');");
});

it('has null sidecar keys when no sidecar files exist', function (): void {
    $page = app(PageService::class)->getRenderedPage('home');

    expect($page['styles'])->toBeNull()
        ->and($page['scripts'])->toBeNull();
});

it('renders sidecar css and js inline on the page response', function (): void {
    $response = $this->get('/about');

    $response->assertOk()
        ->assertSee('<style>.about-hero { color: rebeccapurple; }</style>', false)
        ->assertSee("<script>console.log('about page');</script>", false);
});

it('does not emit empty style or script tags without sidecars', function (): void {
    $response = $this->get('/home');

    $response->assertOk()
        ->assertDontSee('<style></style>', false)
        ->assertDontSee('<script></script>', false);
});
