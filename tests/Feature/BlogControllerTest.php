<?php

declare(strict_types=1);

it('shows a blog post via the .md route and strips the suffix', function (): void {
    $this->get('/blog/hello-world.md')->assertStatus(200);
});

it('shows blog category page via the .md route and strips the suffix', function (): void {
    $this->get('/blog/category/general.md')->assertStatus(200);
});

it('shows blog tag page via the .md route and strips the suffix', function (): void {
    $this->get('/blog/tag/laravel.md')->assertStatus(200);
});

it('shows blog author page via the .md route and strips the suffix', function (): void {
    $this->get('/blog/author/jane-doe.md')->assertStatus(200);
});

it('returns 404 for a media file that does not exist', function (): void {
    $this->get('/blog/media/hello-world/image.png')->assertStatus(404);
});

it('returns 200 for an author with no matching posts using the Str::title fallback', function (): void {
    $this->get('/blog/author/nonexistent-author')->assertStatus(200);
});

it('returns 404 for a blog post that does not exist', function (): void {
    $this->get('/blog/nonexistent-post')->assertStatus(404);
});
