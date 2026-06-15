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

it('appends .html to content page urls when htmlExtension is true', function (): void {
    $service = resolve(SitemapService::class);
    $xml = $service->generate(htmlExtension: true);

    expect($xml)->toContain('/docs/getting-started/introduction.html');
    expect($xml)->toContain('/docs/getting-started/configuration.html');
    expect($xml)->toContain('/docs/advanced/customization.html');
    expect($xml)->toContain('/blog/hello-world.html');
    expect($xml)->toContain('/blog/category/general.html');
    expect($xml)->toContain('/blog/tag/');
});

it('does not append .html to root or blog index urls when htmlExtension is true', function (): void {
    $service = resolve(SitemapService::class);
    $xml = $service->generate(htmlExtension: true);

    expect($xml)->toContain('<loc>http://localhost/</loc>');
    expect($xml)->toContain('<loc>http://localhost/blog</loc>');
    expect($xml)->not->toContain('http://localhost/.html');
    expect($xml)->not->toContain('http://localhost/blog.html');
});
