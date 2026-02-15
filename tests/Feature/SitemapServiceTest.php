<?php

declare(strict_types=1);

use Pergament\Services\SitemapService;

it('generates sitemap with all urls', function (): void {
    $service = resolve(SitemapService::class);
    $xml = $service->generate();

    expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($xml)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    expect($xml)->toContain('<loc>http://localhost/</loc>');
    expect($xml)->toContain('hello-world');
    expect($xml)->toContain('getting-started-with-laravel');
});

it('includes blog categories in sitemap', function (): void {
    $service = resolve(SitemapService::class);
    $xml = $service->generate();

    expect($xml)->toContain('/blog/category/general');
    expect($xml)->toContain('/blog/category/tutorials');
});

it('includes doc page urls', function (): void {
    $service = resolve(SitemapService::class);
    $xml = $service->generate();

    expect($xml)->toContain('/docs/getting-started/introduction');
    expect($xml)->toContain('/docs/getting-started/configuration');
    expect($xml)->toContain('/docs/advanced/customization');
});
