<?php

declare(strict_types=1);

it('renders a doc-page homepage with an explicit chapter/page source', function (): void {
    config()->set('pergament.homepage', ['type' => 'doc-page', 'source' => 'getting-started/introduction']);

    $this->get('/')->assertStatus(200);
});

it('renders a doc-page homepage by auto-resolving to the first page when source has no slash', function (): void {
    config()->set('pergament.homepage', ['type' => 'doc-page', 'source' => 'getting-started']);

    $this->get('/')->assertStatus(200);
});

it('renders a blog-index homepage', function (): void {
    config()->set('pergament.homepage', ['type' => 'blog-index', 'source' => '']);

    $this->get('/')->assertStatus(200);
});

it('redirects to the configured source for a redirect homepage type', function (): void {
    config()->set('pergament.homepage', ['type' => 'redirect', 'source' => '/blog']);

    $this->get('/')->assertRedirect('/blog');
});

it('returns 404 for an unknown homepage type', function (): void {
    config()->set('pergament.homepage', ['type' => 'unknown-type']);

    $this->get('/')->assertStatus(404);
});

it('returns 404 for a page homepage type when the source page does not exist', function (): void {
    config()->set('pergament.homepage', ['type' => 'page', 'source' => 'nonexistent-page']);

    $this->get('/')->assertStatus(404);
});
