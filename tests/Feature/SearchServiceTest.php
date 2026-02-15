<?php

declare(strict_types=1);

use Pergament\Services\SearchService;

it('searches across docs and blog', function (): void {
    $service = resolve(SearchService::class);
    $results = $service->search('started');

    $types = $results->pluck('type')->unique()->toArray();

    expect($results)->not->toBeEmpty();
    expect($types)->toContain('doc');
    expect($types)->toContain('blog');
});

it('returns empty for no matches', function (): void {
    $service = resolve(SearchService::class);
    $results = $service->search('xyznonexistent');

    expect($results)->toBeEmpty();
});
