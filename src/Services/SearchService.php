<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Collection;
use Pergament\Support\UrlGenerator;

final readonly class SearchService
{
    public function __construct(
        private DocumentationService $docs,
        private BlogService $blog,
        private PageService $pages,
    ) {}

    /**
     * Get default suggestions shown when no query is entered.
     *
     * Returns blog index (if enabled), all doc chapter first pages (if enabled),
     * then pages alphabetically to fill up to a maximum of 10 items.
     *
     * @return Collection<int, array{title: string, excerpt: string, url: string, type: string}>
     */
    public function suggestions(): Collection
    {
        $suggestions = collect();

        if (config('pergament.blog.enabled', true)) {
            $suggestions->push([
                'title' => config('pergament.blog.title', 'Blog'),
                'excerpt' => 'Browse all blog posts',
                'url' => route('pergament.blog.index'),
                'type' => 'post',
            ]);
        }

        if (config('pergament.docs.enabled', true)) {
            $docsPrefix = config('pergament.docs.url_prefix', 'docs');

            foreach ($this->docs->getChapters() as $chapter) {
                $firstPage = $chapter->pages->first();

                if ($firstPage !== null) {
                    $suggestions->push([
                        'title' => $chapter->title,
                        'excerpt' => $firstPage->title,
                        'url' => UrlGenerator::path($docsPrefix, $chapter->slug, $firstPage->slug),
                        'type' => 'doc',
                    ]);
                }
            }
        }

        $remaining = max(0, 10 - $suggestions->count());

        if ($remaining > 0 && config('pergament.pages.enabled', true)) {
            $this->pages->getSlugs()
                ->sort()
                ->take($remaining)
                ->each(function (string $slug) use ($suggestions): void {
                    $page = $this->pages->getPage($slug);

                    if ($page !== null) {
                        $suggestions->push([
                            'title' => $page->title,
                            'excerpt' => $page->excerpt,
                            'url' => UrlGenerator::path($page->slug),
                            'type' => 'page',
                        ]);
                    }
                });
        }

        return $suggestions->values();
    }

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
