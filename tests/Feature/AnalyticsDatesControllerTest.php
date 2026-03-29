<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Pergament\Services\AnalyticsService;

beforeEach(function (): void {
    $this->storagePath = sys_get_temp_dir().'/pergament-analytics-dates-test-'.uniqid();
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

    $this->get('/analytics/dates?token=test-secret-token')->assertStatus(404);
});

it('returns 403 when no token is provided', function (): void {
    $this->get('/analytics/dates')->assertStatus(403);
});

it('returns 403 when wrong token is provided', function (): void {
    $this->get('/analytics/dates?token=wrong')->assertStatus(403);
});

it('returns an empty array when no data exists', function (): void {
    $response = $this->get('/analytics/dates?token=test-secret-token');

    $response->assertStatus(200);
    $response->assertJson([]);
});

it('returns a sorted list of dates with recorded data', function (): void {
    $service = resolve(AnalyticsService::class);

    $service->record('/a', CarbonImmutable::parse('2026-02-10T10:00:00Z'));
    $service->record('/b', CarbonImmutable::parse('2026-02-12T10:00:00Z'));
    $service->record('/c', CarbonImmutable::parse('2026-02-11T10:00:00Z'));

    $response = $this->get('/analytics/dates?token=test-secret-token');

    $response->assertStatus(200);
    $response->assertJson(['2026-02-10', '2026-02-11', '2026-02-12']);
});
