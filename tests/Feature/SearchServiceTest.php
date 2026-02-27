<?php

declare(strict_types=1);

use Pergament\Services\SearchService;

it('searches across docs and blog', function (): void {
    $service = resolve(SearchService::class);
    $results = $service->search('started');

    $types = $results->pluck('type')->unique()->toArray();

    expect($results)->not->toBeEmpty();
    expect($types)->toContain('doc');
    expect($types)->toContain('post');
});

it('returns empty for no matches', function (): void {
    $service = resolve(SearchService::class);
    $results = $service->search('xyznonexistent');

    expect($results)->toBeEmpty();
});

it('returns suggestions with blog, docs and pages when no query', function (): void {
    $service = resolve(SearchService::class);
    $results = $service->suggestions();

    expect($results)->not->toBeEmpty();
    expect($results->count())->toBeLessThanOrEqual(10);

    $types = $results->pluck('type')->unique()->values()->all();

    expect($types)->toContain('post');
    expect($types)->toContain('doc');
    expect($types)->toContain('page');
});

it('suggestions include required fields', function (): void {
    $service = resolve(SearchService::class);
    $results = $service->suggestions();

    $results->each(function (array $item): void {
        expect($item)->toHaveKeys(['title', 'excerpt', 'url', 'type']);
        expect($item['title'])->toBeString()->not->toBeEmpty();
        expect($item['url'])->toBeString()->not->toBeEmpty();
    });
});

it('returns json suggestions for empty search query', function (): void {
    $this->getJson('/search?q=')
        ->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['title', 'excerpt', 'url', 'type'],
        ]);
});
