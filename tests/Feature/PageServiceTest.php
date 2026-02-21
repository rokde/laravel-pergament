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
    expect($rendered)->toHaveKeys(['title', 'excerpt', 'htmlContent', 'headings', 'slug', 'layout', 'meta']);
    expect($rendered['htmlContent'])->toContain('This is the homepage content');
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
