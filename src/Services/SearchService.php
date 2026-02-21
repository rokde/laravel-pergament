<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Collection;

final readonly class SearchService
{
    public function __construct(
        private DocumentationService $docs,
        private BlogService $blog,
        private PageService $pages,
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

        if (config('pergament.pages.enabled', true)) {
            $results = $results->merge($this->pages->search($query));
        }

        return $results->values();
    }
}
