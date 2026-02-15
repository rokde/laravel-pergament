<?php

declare(strict_types=1);

it('serves the homepage', function (): void {
    $this->get('/')->assertStatus(200);
});

it('redirects docs index to first page', function (): void {
    $this->get('/docs')->assertRedirect('/docs/getting-started/introduction');
});

it('shows a documentation page', function (): void {
    $this->get('/docs/getting-started/introduction')->assertStatus(200);
});

it('returns 404 for non-existent doc page', function (): void {
    $this->get('/docs/nope/nope')->assertStatus(404);
});

it('shows blog index', function (): void {
    $this->get('/blog')->assertStatus(200);
});

it('shows a blog post', function (): void {
    $this->get('/blog/hello-world')->assertStatus(200);
});

it('shows blog category page', function (): void {
    $this->get('/blog/category/general')->assertStatus(200);
});

it('shows blog tag page', function (): void {
    $this->get('/blog/tag/laravel')->assertStatus(200);
});

it('shows blog author page', function (): void {
    $this->get('/blog/author/jane-doe')->assertStatus(200);
});

it('serves the atom feed', function (): void {
    $this->get('/blog/feed')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
});

it('serves the sitemap', function (): void {
    $this->get('/sitemap.xml')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
});

it('serves robots.txt', function (): void {
    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('serves llms.txt', function (): void {
    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('shows search results', function (): void {
    $this->get('/search?q=hello')->assertStatus(200);
});

it('serves PWA manifest', function (): void {
    $this->get('/manifest.json')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/manifest+json');
});

it('shows a standalone page', function (): void {
    $this->get('/about')->assertStatus(200);
});
