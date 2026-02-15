<?php

declare(strict_types=1);

use Pergament\Data\SeoMeta;
use Pergament\Services\SeoService;

it('returns site defaults when no overrides', function (): void {
    $service = resolve(SeoService::class);
    $seo = $service->resolve([]);

    expect($seo)->toBeInstanceOf(SeoMeta::class);
    expect($seo->title)->toBe('Test Site');
    expect($seo->twitterCard)->toBe('summary_large_image');
});

it('overrides with page front matter using dot notation', function (): void {
    $service = resolve(SeoService::class);
    $seo = $service->resolve(['seo.title' => 'Custom Title']);

    expect($seo->title)->toBe('Custom Title');
});

it('builds page title with site name suffix', function (): void {
    $service = resolve(SeoService::class);
    $seo = $service->resolve([], 'My Page');

    expect($seo->title)->toBe('My Page - Test Site');
});
