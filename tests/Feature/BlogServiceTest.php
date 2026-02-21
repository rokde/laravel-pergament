<?php

declare(strict_types=1);

use Pergament\Data\Author;
use Pergament\Data\BlogPost;
use Pergament\Services\BlogService;

it('returns all posts sorted by date descending', function (): void {
    $service = resolve(BlogService::class);
    $posts = $service->getPosts();

    expect($posts)->toHaveCount(2);
    expect($posts->first())->toBeInstanceOf(BlogPost::class);
    expect($posts->first()->slug)->toBe('getting-started-with-laravel');
    expect($posts->last()->slug)->toBe('hello-world');
});

it('gets a single post by slug', function (): void {
    $service = resolve(BlogService::class);
    $post = $service->getPost('hello-world');

    expect($post)->toBeInstanceOf(BlogPost::class);
    expect($post->title)->toBe('Hello World');
    expect($post->excerpt)->toBe('Our first blog post.');
    expect($post->category)->toBe('General');
});

it('renders a post with HTML content', function (): void {
    $service = resolve(BlogService::class);
    $rendered = $service->getRenderedPost('hello-world');

    expect($rendered)->not->toBeNull();
    expect($rendered)->toHaveKeys(['title', 'excerpt', 'htmlContent', 'slug', 'date', 'authors']);
    expect($rendered['htmlContent'])->toContain('Welcome to our blog');
    expect($rendered['date'])->toBeInstanceOf(Carbon\CarbonImmutable::class);
    expect($rendered['authors'])->toHaveCount(1);
});

it('returns null for non-existent post', function (): void {
    $service = resolve(BlogService::class);

    expect($service->getPost('nonexistent'))->toBeNull();
});

it('paginates posts', function (): void {
    $service = resolve(BlogService::class);
    $paginated = $service->paginate(1, 1);

    expect($paginated)->toHaveKeys(['posts', 'currentPage', 'lastPage', 'total']);
    expect($paginated['posts'])->toHaveCount(1);
    expect($paginated['currentPage'])->toBe(1);
    expect($paginated['lastPage'])->toBe(2);
    expect($paginated['total'])->toBe(2);
});

it('returns categories', function (): void {
    $service = resolve(BlogService::class);
    $categories = $service->getCategories();

    expect($categories)->toHaveCount(2);
    expect($categories->toArray())->toContain('General');
    expect($categories->toArray())->toContain('Tutorials');
});

it('filters posts by category', function (): void {
    $service = resolve(BlogService::class);
    $posts = $service->getPostsByCategory('general');

    expect($posts)->toHaveCount(1);
    expect($posts->first()->title)->toBe('Hello World');
});

it('returns tags', function (): void {
    $service = resolve(BlogService::class);
    $tags = $service->getTags();

    expect($tags)->toContain('intro');
    expect($tags)->toContain('welcome');
    expect($tags)->toContain('laravel');
    expect($tags)->toContain('php');
    expect($tags)->toContain('beginner');
});

it('filters posts by tag', function (): void {
    $service = resolve(BlogService::class);
    $posts = $service->getPostsByTag('laravel');

    expect($posts)->toHaveCount(1);
    expect($posts->first()->slug)->toBe('getting-started-with-laravel');
});

it('returns authors', function (): void {
    $service = resolve(BlogService::class);
    $authors = $service->getAuthors();

    $authorNames = $authors->map(fn (Author $a) => $a->name)->toArray();

    expect($authorNames)->toContain('Jane Doe');
    expect($authorNames)->toContain('John Smith');
});

it('filters posts by author', function (): void {
    $service = resolve(BlogService::class);
    $posts = $service->getPostsByAuthor('jane-doe');

    expect($posts)->toHaveCount(2);
});

it('resolves authors from front matter', function (): void {
    $service = resolve(BlogService::class);
    $post = $service->getPost('hello-world');

    expect($post->authors)->toHaveCount(1);
    expect($post->authors[0])->toBeInstanceOf(Author::class);
    expect($post->authors[0]->name)->toBe('Jane Doe');
});

it('supports multiple authors per post', function (): void {
    $service = resolve(BlogService::class);
    $post = $service->getPost('getting-started-with-laravel');

    expect($post->authors)->toHaveCount(2);
    expect($post->authors[0]->name)->toBe('John Smith');
    expect($post->authors[1]->name)->toBe('Jane Doe');
});

it('searches blog posts', function (): void {
    $service = resolve(BlogService::class);
    $results = $service->search('laravel');

    expect($results)->not->toBeEmpty();
    expect($results->first()['type'])->toBe('post');
    expect($results->first()['title'])->toBe('Getting Started with Laravel');
});
