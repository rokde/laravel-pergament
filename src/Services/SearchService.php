<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Collection;

final class SearchService
{
    public function __construct(
        private DocumentationService $docs,
        private BlogService $blog,
    ) {}

    /**
     * Search across all content types.
     *
     * @return Collection<int, array{title: string, excerpt: string, url: string, type: string}>
     */
    public function search(string $query): Collection
    {
        $results = collect();

        if (config('pergament.docs.enabled', true)) {
            $results = $results->merge($this->docs->search($query));
        }

        if (config('pergament.blog.enabled', true)) {
            $results = $results->merge($this->blog->search($query));
        }

        return $results->values();
    }
}
