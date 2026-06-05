<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pergament\Data\Page;
use Pergament\Support\FrontMatterParser;
use Pergament\Support\UrlGenerator;

final readonly class PageService
{
    public function __construct(
        private FrontMatterParser $frontMatter,
        private MarkdownRenderer $renderer,
        private ContentStatisticsService $statistics,
    ) {}

    public function getPage(string $slug): ?Page
    {
        $filePath = $this->basePath().'/'.$slug.'.md';

        if (! file_exists($filePath)) {
            return null;
        }

        $raw = file_get_contents($filePath);
        $parsed = $this->frontMatter->parse($raw);
        $attributes = $parsed['attributes'];

        return new Page(
            title: (string) ($attributes['title'] ?? Str::title(str_replace('-', ' ', $slug))),
            excerpt: (string) ($attributes['excerpt'] ?? ''),
            slug: $slug,
            content: $parsed['body'],
            layout: $attributes['layout'] ?? null,
            meta: $attributes,
        );
    }

    /**
     * Get the raw markdown body for a page.
     *
     * Returns the content body as-is. If the body does not start with an H1 heading,
     * the page title is prepended so LLMs always have full context.
     */
    public function getRawMarkdown(string $slug): ?string
    {
        $page = $this->getPage($slug);

        if ($page === null) {
            return null;
        }

        if (! preg_match('/^\s*#\s/', $page->content)) {
            return '# '.$page->title."\n\n".$page->content;
        }

        return $page->content;
    }

    /**
     * @return array{title: string, excerpt: string, htmlContent: string, headings: array, slug: string, layout: ?string, meta: array, allowHtml: bool, linkErrors: array<int, string>}|null
     */
    public function getRenderedPage(string $slug): ?array
    {
        $page = $this->getPage($slug);

        if ($page === null) {
            return null;
        }

        $html = $this->renderer->toHtml($page->content);
        $html = $this->renderer->stripFirstH1($html);

        $sourceFile = $this->basePath().'/'.$slug.'.md';
        $result = $this->renderer->resolveContentLinks($html, $sourceFile);
        $html = $result['html'];
        $linkErrors = $result['linkErrors'];

        foreach ($linkErrors as $error) {
            Log::warning('[Pergament] '.$error);
        }

        $headings = $this->renderer->extractHeadings($html);
        $statsConfig = config('pergament.pages.statistics', []);
        $contentStats = $this->statistics->compute($page->content, $sourceFile, $statsConfig);

        return [
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'htmlContent' => $html,
            'headings' => $headings,
            'slug' => $page->slug,
            'layout' => $page->layout,
            'meta' => $page->meta,
            'allowHtml' => ($page->meta['allow_html'] ?? false) === true,
            'statistics' => $contentStats,
            'linkErrors' => $linkErrors,
        ];
    }

    /**
     * Search standalone pages.
     *
     * @return Collection<int, array{title: string, excerpt: string, url: string, type: string}>
     */
    public function search(string $query): Collection
    {
        $query = mb_strtolower($query);

        return $this->getSlugs()
            ->map(fn (string $slug): ?Page => $this->getPage($slug))
            ->filter()
            ->filter(fn (Page $page): bool => str_contains(mb_strtolower($page->title), $query) ||
                str_contains(mb_strtolower($page->excerpt), $query) ||
                str_contains(mb_strtolower($page->content), $query))
            ->map(fn (Page $page): array => [
                'title' => $page->title,
                'excerpt' => $page->excerpt ?: Str::limit(trim(preg_replace('/\s+/', ' ', preg_replace('/[#*_`\[\]()!>~|]+/', '', $page->content))), 160),
                'url' => UrlGenerator::path($page->slug),
                'type' => 'page',
            ])
            ->values();
    }

    /**
     * Get all page slugs from the pages directory.
     *
     * @return Collection<int, string>
     */
    public function getSlugs(): Collection
    {
        $path = $this->basePath();

        if (! is_dir($path)) {
            return collect();
        }

        return collect(scandir($path))
            ->filter(fn (string $file): bool => str_ends_with($file, '.md'))
            ->map(fn (string $file): string => pathinfo($file, PATHINFO_FILENAME))
            ->values();
    }

    private function basePath(): string
    {
        return config('pergament.content_path', 'content').'/'.config('pergament.pages.path', 'pages');
    }
}
