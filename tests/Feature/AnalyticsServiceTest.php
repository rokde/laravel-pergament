<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Pergament\Services\AnalyticsService;

beforeEach(function (): void {
    $this->storagePath = sys_get_temp_dir().'/pergament-analytics-test-'.uniqid();
    config(['pergament.analytics.enabled' => true]);
    config(['pergament.analytics.storage_path' => $this->storagePath]);
});

afterEach(function (): void {
    if (is_dir($this->storagePath)) {
        foreach (glob($this->storagePath.'/*.ndjson') as $file) {
            unlink($file);
        }
        rmdir($this->storagePath);
    }
});

it('records a page view as ndjson', function (): void {
    $service = resolve(AnalyticsService::class);
    $timestamp = CarbonImmutable::parse('2026-01-15T10:00:00Z');

    $service->record('/blog/hello-world', $timestamp);

    $file = $this->storagePath.'/2026-01-15.ndjson';
    expect(file_exists($file))->toBeTrue();

    $line = trim(file($file)[0]);
    $data = json_decode($line, true);
    expect($data['url'])->toBe('/blog/hello-world');
    expect($data['timestamp'])->toBe('2026-01-15T10:00:00+00:00');
});

it('appends multiple hits to the same daily file', function (): void {
    $service = resolve(AnalyticsService::class);
    $date = CarbonImmutable::parse('2026-01-15T12:00:00Z');

    $service->record('/blog/post-one', $date);
    $service->record('/blog/post-two', $date);
    $service->record('/blog/post-one', $date);

    $hits = $service->getHits('2026-01-15');
    expect($hits)->toHaveCount(3);
});

it('returns an empty array when no data exists for a date', function (): void {
    $service = resolve(AnalyticsService::class);

    expect($service->getHits('2026-01-01'))->toBe([]);
});

it('counts hits per url sorted by count descending', function (): void {
    $service = resolve(AnalyticsService::class);
    $date = CarbonImmutable::parse('2026-01-15T12:00:00Z');

    $service->record('/docs/intro', $date);
    $service->record('/blog/post', $date);
    $service->record('/docs/intro', $date);
    $service->record('/docs/intro', $date);

    $byUrl = $service->getHitsByUrl('2026-01-15');

    expect(array_keys($byUrl))->toBe(['/docs/intro', '/blog/post']);
    expect($byUrl['/docs/intro'])->toBe(3);
    expect($byUrl['/blog/post'])->toBe(1);
});

it('returns a summary of hits per day', function (): void {
    $service = resolve(AnalyticsService::class);

    $service->record('/page-a', CarbonImmutable::parse('2026-01-13T10:00:00Z'));
    $service->record('/page-b', CarbonImmutable::parse('2026-01-13T11:00:00Z'));
    $service->record('/page-a', CarbonImmutable::parse('2026-01-14T10:00:00Z'));

    $summary = $service->getSummary(30);

    expect($summary)->toHaveKey('2026-01-13');
    expect($summary['2026-01-13']['total'])->toBe(2);
    expect($summary['2026-01-13']['unique_urls'])->toBe(2);

    expect($summary)->toHaveKey('2026-01-14');
    expect($summary['2026-01-14']['total'])->toBe(1);
    expect($summary['2026-01-14']['unique_urls'])->toBe(1);
});

it('creates the storage directory if it does not exist', function (): void {
    $service = resolve(AnalyticsService::class);

    expect(is_dir($this->storagePath))->toBeFalse();

    $service->record('/some/page', CarbonImmutable::parse('2026-01-15T10:00:00Z'));

    expect(is_dir($this->storagePath))->toBeTrue();
});

it('lists available dates', function (): void {
    $service = resolve(AnalyticsService::class);

    $service->record('/a', CarbonImmutable::parse('2026-01-10T10:00:00Z'));
    $service->record('/b', CarbonImmutable::parse('2026-01-12T10:00:00Z'));

    $dates = $service->getAvailableDates();

    expect($dates)->toBe(['2026-01-10', '2026-01-12']);
});

it('returns empty dates list when storage directory does not exist', function (): void {
    $service = resolve(AnalyticsService::class);

    expect($service->getAvailableDates())->toBe([]);
});
