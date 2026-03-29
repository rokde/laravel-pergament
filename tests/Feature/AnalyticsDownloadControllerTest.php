<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Pergament\Services\AnalyticsService;

beforeEach(function (): void {
    $this->storagePath = sys_get_temp_dir().'/pergament-analytics-dl-test-'.uniqid();
    config(['pergament.analytics.storage_path' => $this->storagePath]);
    config(['pergament.analytics.download.enabled' => true]);
    config(['pergament.analytics.download.token' => 'test-secret-token']);
});

afterEach(function (): void {
    if (is_dir($this->storagePath)) {
        foreach (glob($this->storagePath.'/*.ndjson') as $file) {
            unlink($file);
        }
        rmdir($this->storagePath);
    }
});

it('returns 404 when download is disabled', function (): void {
    config(['pergament.analytics.download.enabled' => false]);

    $this->get('/analytics/download?token=test-secret-token')->assertStatus(404);
});

it('returns 403 when no token is provided', function (): void {
    $this->get('/analytics/download')->assertStatus(403);
});

it('returns 403 when wrong token is provided', function (): void {
    $this->get('/analytics/download?token=wrong')->assertStatus(403);
});

it('returns 400 for invalid date format', function (): void {
    $this->get('/analytics/download?token=test-secret-token&date=not-a-date')->assertStatus(400);
});

it('returns 404 when no data exists for the date', function (): void {
    $this->get('/analytics/download?token=test-secret-token&date=2026-01-01')->assertStatus(404);
});

it('returns the ndjson file for a valid token and date', function (): void {
    $service = resolve(AnalyticsService::class);
    $timestamp = CarbonImmutable::parse('2026-03-01T10:00:00Z');

    $service->record('/blog/hello', $timestamp, false);
    $service->record('/docs/intro', $timestamp, true);

    $response = $this->get('/analytics/download?token=test-secret-token&date=2026-03-01');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/x-ndjson');

    $lines = array_filter(explode("\n", trim($response->getContent())));
    expect($lines)->toHaveCount(2);

    $first = json_decode(array_values($lines)[0], true);
    expect($first['url'])->toBe('/blog/hello');
    expect($first['is_bot'])->toBeFalse();
});

it('defaults to today when no date query param is given', function (): void {
    $service = resolve(AnalyticsService::class);
    $service->record('/page', CarbonImmutable::now(), false);

    $response = $this->get('/analytics/download?token=test-secret-token');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('/page');
});

it('returns 403 when token config is null', function (): void {
    config(['pergament.analytics.download.token' => null]);

    $this->get('/analytics/download?token=anything')->assertStatus(403);
});
