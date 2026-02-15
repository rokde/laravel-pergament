<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pergament\Data\Page;
use Pergament\Support\FrontMatterParser;

final class PageService
{
    public function __construct(
        private FrontMatterParser $frontMatter,
        private MarkdownRenderer $renderer,
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
     * @return array{title: string, excerpt: string, htmlContent: string, headings: array, slug: string, layout: ?string, meta: array, linkErrors: array<int, string>}|null
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

        return [
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'htmlContent' => $html,
            'headings' => $headings,
            'slug' => $page->slug,
            'layout' => $page->layout,
            'meta' => $page->meta,
            'linkErrors' => $linkErrors,
        ];
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
        return config('pergament.content_path').'/'.config('pergament.pages.path', 'pages');
    }
}
