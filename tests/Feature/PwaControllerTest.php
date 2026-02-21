<?php

declare(strict_types=1);

it('serves the service worker with status 200', function (): void {
    $this->get('/sw.js')->assertStatus(200);
});

it('service worker has javascript content type', function (): void {
    $this->get('/sw.js')
        ->assertHeader('Content-Type', 'application/javascript');
});

it('service worker has no-cache cache control header', function (): void {
    $response = $this->get('/sw.js');

    expect($response->headers->get('Cache-Control'))->toContain('no-cache');
});

it('service worker body contains CACHE_NAME constant', function (): void {
    $response = $this->get('/sw.js');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('CACHE_NAME');
});

it('service worker body contains the configured site name', function (): void {
    $response = $this->get('/sw.js');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('Test Site');
});

it('manifest returns correct name field', function (): void {
    config()->set('pergament.pwa.name', 'Test Site');

    $response = $this->get('/manifest.json');

    $response->assertStatus(200);
    $json = json_decode($response->getContent(), true);
    expect($json['name'])->toBe('Test Site');
});

it('manifest returns correct short_name field', function (): void {
    config()->set('pergament.pwa.short_name', 'Test Site');

    $response = $this->get('/manifest.json');

    $response->assertStatus(200);
    $json = json_decode($response->getContent(), true);
    expect($json['short_name'])->toBe('Test Site');
});

it('manifest returns correct start_url field', function (): void {
    $response = $this->get('/manifest.json');

    $response->assertStatus(200);
    $json = json_decode($response->getContent(), true);
    expect($json['start_url'])->toBe('/');
});

it('manifest returns correct display field', function (): void {
    $response = $this->get('/manifest.json');

    $response->assertStatus(200);
    $json = json_decode($response->getContent(), true);
    expect($json['display'])->toBe('standalone');
});
