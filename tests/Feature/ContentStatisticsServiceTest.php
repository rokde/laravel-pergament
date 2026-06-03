<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Pergament\Services\BlogService;
use Pergament\Services\ContentStatisticsService;
use Pergament\Services\DocumentationService;
use Pergament\Services\PageService;

it('returns empty stats when no stats are enabled', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('Hello world', null, []);

    expect($stats)->toBe([]);
});

it('computes reading time', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $content = implode(' ', array_fill(0, 400, 'word'));
    $stats = $service->compute($content, null, ['reading_time' => true]);

    expect($stats)->toHaveKey('reading_time')
        ->and($stats['reading_time'])->toBe(2);
});

it('returns minimum 1 minute reading time for short content', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('Short text.', null, ['reading_time' => true]);

    expect($stats['reading_time'])->toBe(1);
});

it('computes word count', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('Hello world foo bar', null, ['word_count' => true]);

    expect($stats)->toHaveKey('word_count')
        ->and($stats['word_count'])->toBe(4);
});

it('computes word count stripping markdown syntax', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute("# Heading\n\n**bold text** and [link](http://example.com)", null, ['word_count' => true]);

    expect($stats['word_count'])->toBeGreaterThan(0);
});

it('computes character count without whitespace', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('Hello world', null, ['character_count' => true]);

    expect($stats)->toHaveKey('character_count')
        ->and($stats['character_count'])->toBe(10);
});

it('computes paragraph count', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $content = "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.";
    $stats = $service->compute($content, null, ['paragraph_count' => true]);

    expect($stats)->toHaveKey('paragraph_count')
        ->and($stats['paragraph_count'])->toBe(3);
});

it('computes last modified date from file', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $filePath = dirname(__DIR__).'/fixtures/content/docs/0-getting-started/01-introduction.md';
    $stats = $service->compute('content', $filePath, ['last_modified' => true]);

    expect($stats)->toHaveKey('last_modified')
        ->and($stats['last_modified'])->toBeInstanceOf(CarbonImmutable::class);
});

it('computes content age from file', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $filePath = dirname(__DIR__).'/fixtures/content/docs/0-getting-started/01-introduction.md';
    $stats = $service->compute('content', $filePath, ['content_age' => true]);

    expect($stats)->toHaveKey('content_age')
        ->and($stats['content_age'])->toBeInstanceOf(CarbonImmutable::class);
});

it('skips file-based stats when file path is null', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('content', null, ['last_modified' => true, 'content_age' => true]);

    expect($stats)->not->toHaveKey('last_modified')
        ->and($stats)->not->toHaveKey('content_age');
});

it('skips file-based stats when file does not exist', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('content', '/nonexistent/file.md', ['last_modified' => true, 'content_age' => true]);

    expect($stats)->not->toHaveKey('last_modified')
        ->and($stats)->not->toHaveKey('content_age');
});

it('computes multiple stats at once', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $filePath = dirname(__DIR__).'/fixtures/content/docs/0-getting-started/01-introduction.md';
    $content = "First paragraph with some words.\n\nSecond paragraph here.";
    $stats = $service->compute($content, $filePath, [
        'reading_time' => true,
        'word_count' => true,
        'character_count' => true,
        'paragraph_count' => true,
        'last_modified' => true,
        'content_age' => true,
    ]);

    expect($stats)->toHaveKeys(['reading_time', 'word_count', 'character_count', 'paragraph_count', 'last_modified', 'content_age']);
});

it('skips disabled stats even when explicitly set to false', function (): void {
    $service = resolve(ContentStatisticsService::class);
    $stats = $service->compute('Hello world', null, [
        'reading_time' => false,
        'word_count' => false,
    ]);

    expect($stats)->toBe([]);
});

it('includes statistics in documentation rendered page when enabled', function (): void {
    config()->set('pergament.docs.statistics.reading_time', true);
    config()->set('pergament.docs.statistics.word_count', true);

    $service = resolve(DocumentationService::class);
    $page = $service->getRenderedPage('getting-started', 'introduction');

    expect($page)->not->toBeNull()
        ->and($page['statistics'])->toHaveKeys(['reading_time', 'word_count']);
});

it('includes statistics in blog rendered post when enabled', function (): void {
    config()->set('pergament.blog.statistics.reading_time', true);

    $service = resolve(BlogService::class);
    $post = $service->getRenderedPost('hello-world');

    expect($post)->not->toBeNull()
        ->and($post['statistics'])->toHaveKey('reading_time');
});

it('includes statistics in page rendered page when enabled', function (): void {
    config()->set('pergament.pages.statistics.word_count', true);

    $service = resolve(PageService::class);
    $page = $service->getRenderedPage('about');

    expect($page)->not->toBeNull()
        ->and($page['statistics'])->toHaveKey('word_count');
});

it('returns empty statistics when no stats are configured', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getRenderedPage('getting-started', 'introduction');

    expect($page)->not->toBeNull()
        ->and($page['statistics'])->toBe([]);
});
