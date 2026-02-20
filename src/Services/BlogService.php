<?php

declare(strict_types=1);

namespace Pergament\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pergament\Data\Author;
use Pergament\Data\BlogPost;
use Pergament\Support\FrontMatterParser;
use Pergament\Support\UrlGenerator;

final class BlogService
{
    public function __construct(
        private FrontMatterParser $frontMatter,
        private MarkdownRenderer $renderer,
    ) {}

    /**
     * Get all blog posts, sorted by date descending.
     *
     * @return Collection<int, BlogPost>
     */
    public function getPosts(): Collection
    {
        $blogPath = $this->basePath();

        if (! is_dir($blogPath)) {
            return collect();
        }

        return collect(scandir($blogPath))
            ->filter(fn (string $entry): bool => is_dir($blogPath.'/'.$entry) && preg_match('/^\d{4}-\d{2}-\d{2}-.+/', $entry) === 1)
            ->map(fn (string $dirName): ?BlogPost => $this->parsePostDirectory($blogPath.'/'.$dirName, $dirName))
            ->filter()
            ->sortByDesc(fn (BlogPost $post): int => $post->date->timestamp)
            ->values();
    }

    /**
     * Get a single post by slug.
     */
    public function getPost(string $slug): ?BlogPost
    {
        return $this->getPosts()->first(fn (BlogPost $post): bool => $post->slug === $slug);
    }

    /**
     * Get a rendered post with HTML content.
     *
     * @return array{title: string, excerpt: string, htmlContent: string, headings: array, slug: string, date: Carbon, category: ?string, tags: array, authors: array, meta: array, previousPost: array|null, nextPost: array|null, linkErrors: array<int, string>}|null
     */
    public function getRenderedPost(string $slug): ?array
    {
        $posts = $this->getPosts();
        $post = $posts->first(fn (BlogPost $p): bool => $p->slug === $slug);

        if ($post === null) {
            return null;
        }

        $html = $this->renderer->toHtml($post->content);
        $html = $this->renderer->stripFirstH1($html);
        $html = $this->fixMediaPaths($html, $post);

        $sourceFile = $this->resolveSourceFilePath($slug);
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

        $index = $posts->search(fn (BlogPost $p): bool => $p->slug === $slug);
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        return [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'htmlContent' => $html,
            'headings' => $headings,
            'slug' => $post->slug,
            'date' => $post->date,
            'category' => $post->category,
            'tags' => $post->tags,
            'authors' => $post->authors,
            'meta' => $post->meta,
            'previousPost' => $index !== false && $index > 0 ? [
                'title' => $posts->get($index - 1)->title,
                'url' => UrlGenerator::path($blogPrefix, $posts->get($index - 1)->slug),
            ] : null,
            'nextPost' => $index !== false && $index < $posts->count() - 1 ? [
                'title' => $posts->get($index + 1)->title,
                'url' => UrlGenerator::path($blogPrefix, $posts->get($index + 1)->slug),
            ] : null,
            'linkErrors' => $linkErrors,
        ];
    }

    /**
     * Paginate posts for index.
     *
     * @return array{posts: Collection<int, BlogPost>, currentPage: int, lastPage: int, total: int}
     */
    public function paginate(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= (int) config('pergament.blog.per_page', 12);
        $posts = $this->getPosts();
        $total = $posts->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));

        return [
            'posts' => $posts->slice(($page - 1) * $perPage, $perPage)->values(),
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function getCategories(): Collection
    {
        return $this->getPosts()
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function getPostsByCategory(string $category): Collection
    {
        return $this->getPosts()->filter(
            fn (BlogPost $post): bool => $post->category !== null && Str::slug($post->category) === Str::slug($category),
        )->values();
    }

    /**
     * @return Collection<int, string>
     */
    public function getTags(): Collection
    {
        return $this->getPosts()
            ->flatMap(fn (BlogPost $post): array => $post->tags)
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function getPostsByTag(string $tag): Collection
    {
        return $this->getPosts()->filter(
            fn (BlogPost $post): bool => collect($post->tags)->contains(
                fn (string $t): bool => Str::slug($t) === Str::slug($tag),
            ),
        )->values();
    }

    /**
     * @return Collection<int, Author>
     */
    public function getAuthors(): Collection
    {
        return $this->getPosts()
            ->flatMap(fn (BlogPost $post): array => $post->authors)
            ->unique(fn (Author $a): string => $a->slug())
            ->sortBy(fn (Author $a): string => $a->name)
            ->values();
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function getPostsByAuthor(string $authorSlug): Collection
    {
        return $this->getPosts()->filter(
            fn (BlogPost $post): bool => collect($post->authors)->contains(
                fn (Author $a): bool => $a->slug() === $authorSlug,
            ),
        )->values();
    }

    /**
     * Search blog posts.
     *
     * @return Collection<int, array{title: string, excerpt: string, slug: string, date: Carbon, url: string, type: string}>
     */
    public function search(string $query): Collection
    {
        $query = mb_strtolower($query);
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        return $this->getPosts()
            ->filter(fn (BlogPost $post): bool => str_contains(mb_strtolower($post->title), $query) ||
                str_contains(mb_strtolower($post->excerpt), $query) ||
                str_contains(mb_strtolower($post->content), $query))
            ->map(fn (BlogPost $post): array => [
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'slug' => $post->slug,
                'date' => $post->date,
                'url' => UrlGenerator::path($blogPrefix, $post->slug),
                'type' => 'post',
            ])
            ->values();
    }

    /**
     * Resolve a blog media file path.
     */
    public function resolveMediaPath(string $postSlug, string $filename): ?string
    {
        $blogPath = $this->basePath();

        if (! is_dir($blogPath)) {
            return null;
        }

        foreach (scandir($blogPath) as $entry) {
            if (! is_dir($blogPath.'/'.$entry)) {
                continue;
            }

            $slug = $this->extractSlug($entry);
            if ($slug === $postSlug) {
                $filePath = $blogPath.'/'.$entry.'/'.$filename;

                return file_exists($filePath) ? $filePath : null;
            }
        }

        return null;
    }

    private function parsePostDirectory(string $dirPath, string $dirName): ?BlogPost
    {
        $postFile = $dirPath.'/post.md';

        if (! file_exists($postFile)) {
            return null;
        }

        $raw = file_get_contents($postFile);
        $parsed = $this->frontMatter->parse($raw);
        $attributes = $parsed['attributes'];

        $date = $this->extractDate($dirName);
        $slug = $this->extractSlug($dirName);

        $authors = $this->resolveAuthors($attributes);

        return new BlogPost(
            title: (string) ($attributes['title'] ?? Str::title(str_replace('-', ' ', $slug))),
            excerpt: (string) ($attributes['excerpt'] ?? ''),
            slug: $slug,
            content: $parsed['body'],
            date: $date,
            category: isset($attributes['category']) ? (string) $attributes['category'] : null,
            tags: isset($attributes['tags']) && is_array($attributes['tags']) ? $attributes['tags'] : [],
            authors: $authors,
            meta: $attributes,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<int, Author>
     */
    private function resolveAuthors(array $attributes): array
    {
        $postAuthors = $attributes['authors'] ?? $attributes['author'] ?? null;
        $defaultAuthors = config('pergament.blog.default_authors', []);

        if ($postAuthors === null && empty($defaultAuthors)) {
            return [];
        }

        if ($postAuthors !== null) {
            if (is_string($postAuthors)) {
                $postAuthors = [$postAuthors];
            }

            return array_map(
                fn (string|array $author): Author => is_string($author)
                    ? new Author(name: $author)
                    : new Author(
                        name: (string) ($author['name'] ?? 'Unknown'),
                        email: $author['email'] ?? null,
                        url: $author['url'] ?? null,
                        avatar: $author['avatar'] ?? null,
                    ),
                (array) $postAuthors,
            );
        }

        return array_map(
            fn (string|array $author): Author => is_string($author)
                ? new Author(name: $author)
                : new Author(
                    name: (string) ($author['name'] ?? 'Unknown'),
                    email: $author['email'] ?? null,
                    url: $author['url'] ?? null,
                    avatar: $author['avatar'] ?? null,
                ),
            $defaultAuthors,
        );
    }

    private function fixMediaPaths(string $html, BlogPost $post): string
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        return (string) preg_replace_callback(
            '/(<(?:img|source)\s[^>]*?)src="([^"]*?)"([^>]*?>)/i',
            function (array $matches) use ($post, $blogPrefix): string {
                $src = $matches[2];

                if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, '/')) {
                    return $matches[0];
                }

                $newSrc = UrlGenerator::path($blogPrefix, 'media', $post->slug, $src);

                return $matches[1].'src="'.$newSrc.'"'.$matches[3];
            },
            $html,
        );
    }

    private function extractDate(string $dirName): Carbon
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dirName, $match)) {
            return Carbon::parse($match[1]);
        }

        return Carbon::now();
    }

    private function extractSlug(string $dirName): string
    {
        return preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $dirName);
    }

    private function resolveSourceFilePath(string $slug): ?string
    {
        $blogPath = $this->basePath();

        if (! is_dir($blogPath)) {
            return null;
        }

        foreach (scandir($blogPath) as $entry) {
            if (! is_dir($blogPath.'/'.$entry)) {
                continue;
            }

            if ($this->extractSlug($entry) === $slug) {
                $filePath = $blogPath.'/'.$entry.'/post.md';

                return file_exists($filePath) ? $filePath : null;
            }
        }

        return null;
    }

    private function basePath(): string
    {
        return config('pergament.content_path').'/'.config('pergament.blog.path', 'blog');
    }
}
