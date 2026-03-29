<?php

declare(strict_types=1);

use Pergament\Services\AnalyticsService;

beforeEach(function (): void {
    $this->storagePath = sys_get_temp_dir().'/pergament-analytics-mw-test-'.uniqid();
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

it('does not track when analytics is disabled', function (): void {
    config(['pergament.analytics.enabled' => false]);

    $this->get('/')->assertStatus(200);

    $service = resolve(AnalyticsService::class);
    expect($service->getAvailableDates())->toBe([]);
});

it('tracks a blog post page view when analytics is enabled', function (): void {
    config(['pergament.analytics.enabled' => true]);

    $this->get('/blog/hello-world')->assertStatus(200);

    $service = resolve(AnalyticsService::class);
    $today = now()->format('Y-m-d');
    $hits = $service->getHits($today);

    expect($hits)->toHaveCount(1);
    expect($hits[0]['url'])->toBe('/blog/hello-world');
    expect($hits[0]['is_bot'])->toBeFalse();
});

it('tracks a documentation page view when analytics is enabled', function (): void {
    config(['pergament.analytics.enabled' => true]);

    $this->get('/docs/getting-started/introduction')->assertStatus(200);

    $service = resolve(AnalyticsService::class);
    $today = now()->format('Y-m-d');
    $hits = $service->getHits($today);

    expect($hits)->toHaveCount(1);
    expect($hits[0]['url'])->toBe('/docs/getting-started/introduction');
    expect($hits[0]['is_bot'])->toBeFalse();
});

it('does not track markdown export requests', function (): void {
    config(['pergament.analytics.enabled' => true]);

    $this->get('/blog/hello-world.md')->assertStatus(200);

    $service = resolve(AnalyticsService::class);
    $today = now()->format('Y-m-d');

    expect($service->getHits($today))->toBe([]);
});

it('tracks bot user agents with is_bot true', function (): void {
    config(['pergament.analytics.enabled' => true]);

    $this->get('/blog/hello-world', ['User-Agent' => 'Googlebot/2.1'])->assertStatus(200);

    $service = resolve(AnalyticsService::class);
    $today = now()->format('Y-m-d');
    $hits = $service->getHits($today);

    expect($hits)->toHaveCount(1);
    expect($hits[0]['is_bot'])->toBeTrue();
});

it('tracks regular browser requests with is_bot false', function (): void {
    config(['pergament.analytics.enabled' => true]);

    $this->get('/blog/hello-world', ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X)'])->assertStatus(200);

    $service = resolve(AnalyticsService::class);
    $today = now()->format('Y-m-d');
    $hits = $service->getHits($today);

    expect($hits)->toHaveCount(1);
    expect($hits[0]['is_bot'])->toBeFalse();
});
