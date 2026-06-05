<?php

declare(strict_types=1);

it('serves the configured favicon with status 200', function (): void {
    $this->get('/favicon.ico')->assertStatus(200);
});

it('serves the favicon file contents from the content directory', function (): void {
    $expected = file_get_contents(__DIR__.'/../fixtures/content/favicon.ico');

    $response = $this->get('/favicon.ico');

    $response->assertStatus(200);
    expect($response->getContent())->toBe($expected);
});

it('sets a public cache control header on the favicon', function (): void {
    $response = $this->get('/favicon.ico');

    expect($response->headers->get('Cache-Control'))->toContain('public');
});

it('returns 404 when the configured favicon file is missing', function (): void {
    config()->set('pergament.favicon', 'does-not-exist.ico');

    $this->get('/favicon.ico')->assertStatus(404);
});

it('renders a favicon link tag pointing at the configured favicon', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('<link rel="icon" href="/favicon.ico">');
});
