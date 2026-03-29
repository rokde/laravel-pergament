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

it('records a page view as ndjson with is_bot field', function (): void {
    $service = resolve(AnalyticsService::class);
    $timestamp = CarbonImmutable::parse('2026-01-15T10:00:00Z');

    $service->record('/blog/hello-world', $timestamp, false);

    $file = $this->storagePath.'/2026-01-15.ndjson';
    expect(file_exists($file))->toBeTrue();

    $line = trim(file($file)[0]);
    $data = json_decode($line, true);
    expect($data['url'])->toBe('/blog/hello-world');
    expect($data['timestamp'])->toBe('2026-01-15T10:00:00+00:00');
    expect($data['is_bot'])->toBeFalse();
});

it('records a bot page view with is_bot true', function (): void {
    $service = resolve(AnalyticsService::class);
    $timestamp = CarbonImmutable::parse('2026-01-15T10:00:00Z');

    $service->record('/blog/hello-world', $timestamp, true);

    $file = $this->storagePath.'/2026-01-15.ndjson';
    $line = trim(file($file)[0]);
    $data = json_decode($line, true);
    expect($data['is_bot'])->toBeTrue();
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

it('returns a summary of hits per day with bot and user breakdown', function (): void {
    $service = resolve(AnalyticsService::class);

    $dayOne = CarbonImmutable::today()->subDays(2)->setTime(10, 0, 0);
    $dayTwo = CarbonImmutable::today()->subDay()->setTime(10, 0, 0);

    $service->record('/page-a', $dayOne, false);
    $service->record('/page-b', $dayOne->addHour(), true);
    $service->record('/page-a', $dayTwo, false);

    $summary = $service->getSummary(30);

    $dateOne = $dayOne->format('Y-m-d');
    $dateTwo = $dayTwo->format('Y-m-d');

    expect($summary)->toHaveKey($dateOne);
    expect($summary[$dateOne]['total'])->toBe(2);
    expect($summary[$dateOne]['users'])->toBe(1);
    expect($summary[$dateOne]['bots'])->toBe(1);
    expect($summary[$dateOne]['unique_urls'])->toBe(2);

    expect($summary)->toHaveKey($dateTwo);
    expect($summary[$dateTwo]['total'])->toBe(1);
    expect($summary[$dateTwo]['users'])->toBe(1);
    expect($summary[$dateTwo]['bots'])->toBe(0);
    expect($summary[$dateTwo]['unique_urls'])->toBe(1);
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

it('merges ndjson entries into local storage and skips duplicates', function (): void {
    $service = resolve(AnalyticsService::class);
    $timestamp = CarbonImmutable::parse('2026-03-01T10:00:00Z');

    // Pre-seed one local entry
    $service->record('/existing', $timestamp, false);

    $ndjson = implode("\n", [
        json_encode(['url' => '/existing', 'timestamp' => '2026-03-01T10:00:00+00:00', 'is_bot' => false]),
        json_encode(['url' => '/new-page', 'timestamp' => '2026-03-01T11:00:00+00:00', 'is_bot' => true]),
    ]);

    $added = $service->mergeFromNdjson('2026-03-01', $ndjson);

    expect($added)->toBe(1);

    $hits = $service->getHits('2026-03-01');
    expect($hits)->toHaveCount(2);
    expect($hits[1]['url'])->toBe('/new-page');
    expect($hits[1]['is_bot'])->toBeTrue();
});

it('merges into a new date file when no local data exists', function (): void {
    $service = resolve(AnalyticsService::class);

    $ndjson = implode("\n", [
        json_encode(['url' => '/page-a', 'timestamp' => '2026-03-05T08:00:00+00:00', 'is_bot' => false]),
        json_encode(['url' => '/page-b', 'timestamp' => '2026-03-05T09:00:00+00:00', 'is_bot' => false]),
    ]);

    $added = $service->mergeFromNdjson('2026-03-05', $ndjson);

    expect($added)->toBe(2);
    expect($service->getHits('2026-03-05'))->toHaveCount(2);
});

it('returns zero when all remote entries already exist locally', function (): void {
    $service = resolve(AnalyticsService::class);
    $timestamp = CarbonImmutable::parse('2026-03-10T10:00:00Z');

    $service->record('/page', $timestamp, false);

    $ndjson = json_encode(['url' => '/page', 'timestamp' => '2026-03-10T10:00:00+00:00', 'is_bot' => false]);

    $added = $service->mergeFromNdjson('2026-03-10', $ndjson);

    expect($added)->toBe(0);
    expect($service->getHits('2026-03-10'))->toHaveCount(1);
});
