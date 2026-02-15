<?php

declare(strict_types=1);

use Pergament\Services\FeedService;

it('generates a valid atom feed', function (): void {
    $service = resolve(FeedService::class);
    $xml = $service->atom();

    expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($xml)->toContain('<feed xmlns="http://www.w3.org/2005/Atom">');
    expect($xml)->toContain('<entry>');
    expect($xml)->toContain('Hello World');
    expect($xml)->toContain('Getting Started with Laravel');
});

it('generates a valid rss feed', function (): void {
    $service = resolve(FeedService::class);
    $xml = $service->rss();

    expect($xml)->toContain('<rss version="2.0"');
    expect($xml)->toContain('<channel>');
    expect($xml)->toContain('<item>');
    expect($xml)->toContain('Hello World');
});

it('includes post authors in feed', function (): void {
    $service = resolve(FeedService::class);
    $xml = $service->atom();

    expect($xml)->toContain('<author>');
    expect($xml)->toContain('<name>Jane Doe</name>');
});

it('respects feed limit config', function (): void {
    config()->set('pergament.blog.feed.limit', 1);

    $service = resolve(FeedService::class);
    $xml = $service->atom();

    $entryCount = mb_substr_count($xml, '<entry>');
    expect($entryCount)->toBe(1);
});
