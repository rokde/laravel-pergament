<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pergament\Data\DocChapter;
use Pergament\Data\DocHeading;
use Pergament\Data\DocPage;
use Pergament\Support\FrontMatterParser;
use Pergament\Support\UrlGenerator;

final readonly class DocumentationService
{
    public function __construct(
        private FrontMatterParser $frontMatter,
        private MarkdownRenderer $renderer,
    ) {}

    /**
     * @return Collection<int, DocChapter>
     */
    public function getChapters(): Collection
    {
        $docsPath = $this->basePath();

        if (! is_dir($docsPath)) {
            return collect();
        }

        return collect(scandir($docsPath))
            ->filter(fn (string $entry): bool => is_dir($docsPath.'/'.$entry) && preg_match('/^\d+-/', $entry) === 1)
            ->sort()
            ->values()
            ->map(function (string $dirName) use ($docsPath): DocChapter {
                $slug = $this->removeNumberPrefix($dirName);
                $title = Str::title(str_replace('-', ' ', $slug));
                $pages = $this->getPagesForChapter($docsPath.'/'.$dirName);

                return new DocChapter(title: $title, slug: $slug, pages: $pages);
            });
    }

    /**
     * @return Collection<int, array{title: string, slug: string, pages: array<int, array{title: string, slug: string}>}>
     */
    public function getNavigation(): Collection
    {
        return $this->getChapters()->map(fn (DocChapter $chapter): array => [
            'title' => $chapter->title,
            'slug' => $chapter->slug,
            'pages' => $chapter->pages->map(fn (DocPage $page): array => [
                'title' => $page->title,
                'slug' => $page->slug,
            ])->all(),
        ]);
    }

    public function getPage(string $chapterSlug, string $pageSlug): ?DocPage
    {
        $docsPath = $this->basePath();
        $chapterDir = $this->findDirectoryBySlug($docsPath, $chapterSlug);

        if ($chapterDir === null) {
            return null;
        }

        $filePath = $this->findFileBySlug($chapterDir, $pageSlug);

        if ($filePath === null) {
            return null;
        }

        return $this->parsePageFile($filePath, $pageSlug);
    }

    /**
     * @return array{title: string, excerpt: string, htmlContent: string, headings: array<int, DocHeading>, slug: string, previousPage: array{title: string, url: string}|null, nextPage: array{title: string, url: string}|null, linkErrors: array<int, string>}|null
     */
    public function getRenderedPage(string $chapterSlug, string $pageSlug): ?array
    {
        $page = $this->getPage($chapterSlug, $pageSlug);

        if ($page === null) {
            return null;
        }

        $html = $this->renderer->toHtml($page->content);
        $html = $this->renderer->stripFirstH1($html);
        $html = $this->fixMediaPaths($html, $chapterSlug);

        $sourceFile = $this->resolveSourceFilePath($chapterSlug, $pageSlug);
        $linkErrors = [];

        if ($sourceFile !== null) {
            $result = $this->renderer->resolveContentLinks($html, $sourceFile);
            $html = $result['html'];
            $linkErrors = $result['linkErrors'];

            foreach ($linkErrors as $error) {
                Log::warning('[Pergament] '.$error);
            }
        }

        $headings = $this->renderer->extractHeadings($html);
        $adjacent = $this->getAdjacentPages($chapterSlug, $pageSlug);
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        return [
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'htmlContent' => $html,
            'headings' => $headings,
            'slug' => $page->slug,
            'meta' => $page->meta,
            'previousPage' => $adjacent['previous'] ? [
                'title' => $adjacent['previous']['title'],
                'url' => UrlGenerator::path($docsPrefix, $adjacent['previous']['chapter'], $adjacent['previous']['page']),
            ] : null,
            'nextPage' => $adjacent['next'] ? [
                'title' => $adjacent['next']['title'],
                'url' => UrlGenerator::path($docsPrefix, $adjacent['next']['chapter'], $adjacent['next']['page']),
            ] : null,
            'linkErrors' => $linkErrors,
        ];
    }

    /**
     * @return array{chapter: string, page: string}|null
     */
    public function getFirstPage(): ?array
    {
        $chapters = $this->getChapters();

        if ($chapters->isEmpty()) {
            return null;
        }

        $first = $chapters->first();

        if ($first->pages->isEmpty()) {
            return null;
        }

        return ['chapter' => $first->slug, 'page' => $first->pages->first()->slug];
    }

    /**
     * Search across all doc pages.
     *
     * @return Collection<int, array{title: string, excerpt: string, chapter: string, chapterTitle: string, page: string, url: string}>
     */
    public function search(string $query): Collection
    {
        $query = mb_strtolower($query);
        $results = collect();
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        foreach ($this->getChapters() as $chapter) {
            foreach ($chapter->pages as $page) {
                if (
                    str_contains(mb_strtolower($page->title), $query) ||
                    str_contains(mb_strtolower($page->excerpt), $query) ||
                    str_contains(mb_strtolower($page->content), $query)
                ) {
                    $results->push([
                        'title' => $page->title,
                        'excerpt' => $page->excerpt ?: Str::limit(trim(preg_replace('/\s+/', ' ', preg_replace('/[#*_`\[\]()!>~|]+/', '', $page->content))), 160),
                        'chapter' => $chapter->slug,
                        'chapterTitle' => $chapter->title,
                        'page' => $page->slug,
                        'url' => UrlGenerator::path($docsPrefix, $chapter->slug, $page->slug),
                        'type' => 'doc',
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Check for dark/light themed image variants.
     *
     * @return array{hasDark: bool, hasLight: bool}
     */
    public function resolveThemedImageVariants(string $chapterSlug, string $filename): array
    {
        $docsPath = $this->basePath();
        $chapterDir = $this->findDirectoryBySlug($docsPath, $chapterSlug);

        if ($chapterDir === null) {
            return ['hasDark' => false, 'hasLight' => false];
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        return [
            'hasDark' => file_exists($chapterDir.'/'.$name.'.dark.'.$extension),
            'hasLight' => file_exists($chapterDir.'/'.$name.'.light.'.$extension),
        ];
    }

    /**
     * Resolve a slug-based file path to a real filesystem path.
     */
    public function resolveMediaPath(string $relativePath): ?string
    {
        $docsPath = $this->basePath();
        $parts = explode('/', $relativePath, 2);

        if (count($parts) < 2) {
            return null;
        }

        $chapterDir = $this->findDirectoryBySlug($docsPath, $parts[0]);

        if ($chapterDir === null) {
            return null;
        }

        $filePath = $chapterDir.'/'.$parts[1];

        return file_exists($filePath) ? $filePath : null;
    }

    /**
     * @return array{previous: array{title: string, chapter: string, page: string}|null, next: array{title: string, chapter: string, page: string}|null}
     */
    private function getAdjacentPages(string $chapterSlug, string $pageSlug): array
    {
        $flatPages = [];

        foreach ($this->getChapters() as $chapter) {
            foreach ($chapter->pages as $page) {
                $flatPages[] = [
                    'title' => $page->title,
                    'chapter' => $chapter->slug,
                    'page' => $page->slug,
                ];
            }
        }

        $currentIndex = array_find_key($flatPages, fn ($entry): bool => $entry['chapter'] === $chapterSlug && $entry['page'] === $pageSlug);

        return [
            'previous' => $currentIndex !== null && $currentIndex > 0 ? $flatPages[$currentIndex - 1] : null,
            'next' => $currentIndex !== null && $currentIndex < count($flatPages) - 1 ? $flatPages[$currentIndex + 1] : null,
        ];
    }

    private function fixMediaPaths(string $html, string $chapterSlug): string
    {
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        return (string) preg_replace_callback(
            '/(<(?:img|source)\s[^>]*?)src="([^"]*?)"([^>]*?>)/i',
            function (array $matches) use ($chapterSlug, $docsPrefix): string {
                $src = $matches[2];

                if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, '/')) {
                    return $matches[0];
                }

                $tag = mb_strtolower(mb_substr($matches[1], 1, 3));
                if ($tag === 'img') {
                    $variants = $this->resolveThemedImageVariants($chapterSlug, $src);

                    if ($variants['hasDark'] || $variants['hasLight']) {
                        return $this->buildThemedImages($matches, $chapterSlug, $src, $variants, $docsPrefix);
                    }
                }

                $newSrc = UrlGenerator::path($docsPrefix, 'media', $chapterSlug, $src);

                return $matches[1].'src="'.$newSrc.'"'.$matches[3];
            },
            $html,
        );
    }

    /**
     * @param  array<int, string>  $matches
     * @param  array{hasDark: bool, hasLight: bool}  $variants
     */
    private function buildThemedImages(array $matches, string $chapterSlug, string $src, array $variants, string $docsPrefix): string
    {
        $extension = pathinfo($src, PATHINFO_EXTENSION);
        $name = pathinfo($src, PATHINFO_FILENAME);

        $lightSrc = $variants['hasLight']
            ? UrlGenerator::path($docsPrefix, 'media', $chapterSlug, $name.'.light.'.$extension)
            : UrlGenerator::path($docsPrefix, 'media', $chapterSlug, $src);

        $darkSrc = $variants['hasDark']
            ? UrlGenerator::path($docsPrefix, 'media', $chapterSlug, $name.'.dark.'.$extension)
            : UrlGenerator::path($docsPrefix, 'media', $chapterSlug, $src);

        $lightTag = $matches[1].'src="'.$lightSrc.'"'.' class="pergament-img-light"'.$matches[3];
        $darkTag = $matches[1].'src="'.$darkSrc.'"'.' class="pergament-img-dark"'.$matches[3];

        return $lightTag.$darkTag;
    }

    private function resolveSourceFilePath(string $chapterSlug, string $pageSlug): ?string
    {
        $docsPath = $this->basePath();
        $chapterDir = $this->findDirectoryBySlug($docsPath, $chapterSlug);

        if ($chapterDir === null) {
            return null;
        }

        return $this->findFileBySlug($chapterDir, $pageSlug);
    }

    private function basePath(): string
    {
        return config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    }

    /**
     * @return Collection<int, DocPage>
     */
    private function getPagesForChapter(string $chapterPath): Collection
    {
        if (! is_dir($chapterPath)) {
            return collect();
        }

        return collect(scandir($chapterPath))
            ->filter(fn (string $file): bool => str_ends_with($file, '.md'))
            ->sort()
            ->values()
            ->map(fn (string $file): DocPage => $this->parsePageFile(
                $chapterPath.'/'.$file,
                $this->removeNumberPrefixAndExtension($file),
            ));
    }

    private function parsePageFile(string $filePath, string $slug): DocPage
    {
        $raw = file_get_contents($filePath);
        $parsed = $this->frontMatter->parse($raw);
        $attributes = $parsed['attributes'];

        return new DocPage(
            title: (string) ($attributes['title'] ?? Str::title(str_replace('-', ' ', $slug))),
            excerpt: (string) ($attributes['excerpt'] ?? ''),
            slug: $slug,
            content: $parsed['body'],
            meta: $attributes,
        );
    }

    private function findDirectoryBySlug(string $basePath, string $slug): ?string
    {
        if (! is_dir($basePath)) {
            return null;
        }

        foreach (scandir($basePath) as $entry) {
            if (! is_dir($basePath.'/'.$entry)) {
                continue;
            }

            if ($this->removeNumberPrefix($entry) === $slug) {
                return $basePath.'/'.$entry;
            }
        }

        return null;
    }

    private function findFileBySlug(string $dirPath, string $slug): ?string
    {
        foreach (scandir($dirPath) as $file) {
            if (! str_ends_with($file, '.md')) {
                continue;
            }

            if ($this->removeNumberPrefixAndExtension($file) === $slug) {
                return $dirPath.'/'.$file;
            }
        }

        return null;
    }

    private function removeNumberPrefix(string $name): string
    {
        return preg_replace('/^\d+-/', '', $name);
    }

    private function removeNumberPrefixAndExtension(string $filename): string
    {
        return $this->removeNumberPrefix(pathinfo($filename, PATHINFO_FILENAME));
    }
}
