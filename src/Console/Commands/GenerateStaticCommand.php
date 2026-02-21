<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Pergament\Services\BlogService;
use Pergament\Services\DocumentationService;
use Pergament\Services\FeedService;
use Pergament\Services\PageService;
use Pergament\Services\SeoService;
use Pergament\Services\SitemapService;
use Pergament\Support\UrlGenerator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class GenerateStaticCommand extends Command
{
    protected $signature = 'pergament:generate-static
                            {output-dir : The directory to write static files to}
                            {--prefix= : Override URL prefix for this export}
                            {--base-url= : Override site URL for sitemap/feed}
                            {--clean : Remove output directory before generating}';

    protected $description = 'Generate a static HTML site from Pergament content';

    /** @var array<int, string> */
    private array $errors = [];

    public function handle(
        DocumentationService $docsService,
        BlogService $blogService,
        PageService $pageService,
        SitemapService $sitemapService,
        FeedService $feedService,
        SeoService $seoService,
    ): int {
        $outputDir = mb_rtrim((string) $this->argument('output-dir'), '/');

        $originalPrefix = config('pergament.prefix');
        $originalSiteUrl = config('pergament.site.url');

        try {
            if ($this->option('prefix') !== null) {
                config()->set('pergament.prefix', $this->option('prefix'));
            }

            if ($this->option('base-url') !== null) {
                config()->set('pergament.site.url', $this->option('base-url'));
            }

            if ($this->option('clean') && is_dir($outputDir)) {
                $this->removeDirectory($outputDir);
            }

            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $this->generateHomepage($pageService, $docsService, $blogService, $seoService, $outputDir);

            if (config('pergament.docs.enabled', true)) {
                $this->generateDocIndex($docsService, $outputDir);
                $this->generateDocPages($docsService, $seoService, $outputDir);
                $this->copyDocMedia($docsService, $outputDir);
            }

            if (config('pergament.blog.enabled', true)) {
                $this->generateBlogIndex($blogService, $seoService, $outputDir);
                $this->generateBlogPosts($blogService, $seoService, $outputDir);
                $this->generateCategoryPages($blogService, $seoService, $outputDir);
                $this->generateTagPages($blogService, $seoService, $outputDir);
                $this->generateAuthorPages($blogService, $seoService, $outputDir);

                $this->copyBlogMedia($blogService, $outputDir);

                if (config('pergament.blog.feed.enabled', true)) {
                    $this->generateFeed($feedService, $outputDir);
                }
            }

            if (config('pergament.pages.enabled', true)) {
                $this->generatePages($pageService, $seoService, $outputDir);
            }

            if (config('pergament.sitemap.enabled', true)) {
                $this->generateSitemap($sitemapService, $outputDir);
            }

            if (config('pergament.robots.enabled', true)) {
                $this->generateRobots($outputDir);
            }

            if (config('pergament.llms.enabled', true)) {
                $this->generateLlms($outputDir);
            }

            if (count($this->errors) > 0) {
                $this->components->warn('Static site generated with '.count($this->errors).' error(s):');
                foreach ($this->errors as $error) {
                    $this->components->error($error);
                }

                return self::SUCCESS;
            }

            $this->components->info('Static site generated successfully.');

            return self::SUCCESS;
        } finally {
            config()->set('pergament.prefix', $originalPrefix);
            config()->set('pergament.site.url', $originalSiteUrl);
        }
    }

    private function generateHomepage(
        PageService $pageService,
        DocumentationService $docsService,
        BlogService $blogService,
        SeoService $seoService,
        string $outputDir,
    ): void {
        $homepage = config('pergament.homepage', []);
        $type = $homepage['type'] ?? 'page';
        $source = $homepage['source'] ?? 'home';

        try {
            $html = match ($type) {
                'page' => $this->renderHomepagePage($pageService, $seoService, $source),
                'doc-page' => $this->renderHomepageDocPage($docsService, $seoService, $source),
                'blog-index' => $this->renderHomepageBlogIndex($blogService, $seoService),
                'redirect' => $this->renderRedirect($source),
                default => null,
            };

            if ($html !== null) {
                $this->writeFile($outputDir.'/index.html', $this->postProcessHtml($html));
            }
        } catch (Throwable $e) {
            $this->errors[] = "Homepage: {$e->getMessage()}";
        }
    }

    private function renderHomepagePage(PageService $pageService, SeoService $seoService, string $slug): ?string
    {
        $page = $pageService->getRenderedPage($slug);

        if ($page === null) {
            return null;
        }

        $this->collectLinkErrors($page);
        $seo = $seoService->resolve($page['meta'], $page['title']);
        $layout = $page['layout'] ?? 'default';

        return view('pergament::pages.show', [
            'page' => $page,
            'seo' => $seo,
            'layout' => $layout,
            'isHomepage' => true,
        ])->render();
    }

    private function renderHomepageDocPage(DocumentationService $docsService, SeoService $seoService, string $source): ?string
    {
        $parts = explode('/', $source, 2);

        if (count($parts) < 2) {
            $first = $docsService->getFirstPage();

            if ($first === null) {
                return null;
            }

            $parts = [$first['chapter'], $first['page']];
        }

        $page = $docsService->getRenderedPage($parts[0], $parts[1]);

        if ($page === null) {
            return null;
        }

        $this->collectLinkErrors($page);
        $seo = $seoService->resolve($page['meta'], $page['title']);

        return view('pergament::docs.show', [
            'page' => $page,
            'navigation' => $docsService->getNavigation(),
            'currentChapter' => $parts[0],
            'currentPage' => $parts[1],
            'seo' => $seo,
        ])->render();
    }

    private function renderHomepageBlogIndex(BlogService $blogService, SeoService $seoService): string
    {
        $paginated = $blogService->paginate(1);
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'));

        return view('pergament::blog.index', [
            'posts' => $paginated['posts'],
            'currentPage' => $paginated['currentPage'],
            'lastPage' => $paginated['lastPage'],
            'total' => $paginated['total'],
            'seo' => $seo,
        ])->render();
    }

    private function renderRedirect(string $target): string
    {
        return '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url='.e($target).'"></head><body></body></html>';
    }

    private function generateDocIndex(DocumentationService $docsService, string $outputDir): void
    {
        try {
            $first = $docsService->getFirstPage();

            if ($first === null) {
                return;
            }

            $docsPrefix = config('pergament.docs.url_prefix', 'docs');
            $target = UrlGenerator::path($docsPrefix, $first['chapter'], $first['page']);
            $html = $this->renderRedirect($target);

            $dir = $outputDir.'/'.$docsPrefix;
            $this->writeFile($dir.'/index.html', $html);
        } catch (Throwable $e) {
            $this->errors[] = "Doc index: {$e->getMessage()}";
        }
    }

    private function generateDocPages(DocumentationService $docsService, SeoService $seoService, string $outputDir): void
    {
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        foreach ($docsService->getChapters() as $chapter) {
            foreach ($chapter->pages as $page) {
                try {
                    $pageData = $docsService->getRenderedPage($chapter->slug, $page->slug);

                    if ($pageData === null) {
                        continue;
                    }

                    $this->collectLinkErrors($pageData);
                    $seo = $seoService->resolve($pageData['meta'], $pageData['title']);

                    $html = view('pergament::docs.show', [
                        'page' => $pageData,
                        'navigation' => $docsService->getNavigation(),
                        'currentChapter' => $chapter->slug,
                        'currentPage' => $page->slug,
                        'seo' => $seo,
                    ])->render();

                    $path = $outputDir.'/'.$docsPrefix.'/'.$chapter->slug.'/'.$page->slug.'/index.html';
                    $this->writeFile($path, $this->postProcessHtml($html));
                } catch (Throwable $e) {
                    $this->errors[] = "Doc page {$chapter->slug}/{$page->slug}: {$e->getMessage()}";
                }
            }
        }
    }

    private function generateBlogIndex(BlogService $blogService, SeoService $seoService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'));
        $categories = $blogService->getCategories();
        $tags = $blogService->getTags();

        $firstPage = $blogService->paginate(1);
        $lastPage = $firstPage['lastPage'];

        for ($page = 1; $page <= $lastPage; $page++) {
            try {
                $paginated = $blogService->paginate($page);

                $html = view('pergament::blog.index', [
                    'posts' => $paginated['posts'],
                    'currentPage' => $paginated['currentPage'],
                    'lastPage' => $paginated['lastPage'],
                    'total' => $paginated['total'],
                    'categories' => $categories,
                    'tags' => $tags,
                    'seo' => $seo,
                ])->render();

                $html = $this->postProcessHtml($html);

                if ($page === 1) {
                    $this->writeFile($outputDir.'/'.$blogPrefix.'/index.html', $html);
                }

                $this->writeFile($outputDir.'/'.$blogPrefix.'/page/'.$page.'/index.html', $html);
            } catch (Throwable $e) {
                $this->errors[] = "Blog index page {$page}: {$e->getMessage()}";
            }
        }
    }

    private function generateBlogPosts(BlogService $blogService, SeoService $seoService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        foreach ($blogService->getPosts() as $post) {
            try {
                $rendered = $blogService->getRenderedPost($post->slug);

                if ($rendered === null) {
                    continue;
                }

                $this->collectLinkErrors($rendered);
                $seo = $seoService->resolve($rendered['meta'], $rendered['title']);

                $html = view('pergament::blog.show', [
                    'post' => $rendered,
                    'seo' => $seo,
                ])->render();

                $path = $outputDir.'/'.$blogPrefix.'/'.$post->slug.'/index.html';
                $this->writeFile($path, $this->postProcessHtml($html));
            } catch (Throwable $e) {
                $this->errors[] = "Blog post {$post->slug}: {$e->getMessage()}";
            }
        }
    }

    private function generateCategoryPages(BlogService $blogService, SeoService $seoService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        foreach ($blogService->getCategories() as $category) {
            try {
                $posts = $blogService->getPostsByCategory($category);
                $categorySlug = Str::slug($category);
                $categoryTitle = Str::title(str_replace('-', ' ', $categorySlug));
                $seo = $seoService->resolve([], $categoryTitle);

                $html = view('pergament::blog.category', [
                    'posts' => $posts,
                    'category' => $categoryTitle,
                    'categorySlug' => $categorySlug,
                    'seo' => $seo,
                ])->render();

                $path = $outputDir.'/'.$blogPrefix.'/category/'.$categorySlug.'/index.html';
                $this->writeFile($path, $this->postProcessHtml($html));
            } catch (Throwable $e) {
                $this->errors[] = "Category {$category}: {$e->getMessage()}";
            }
        }
    }

    private function generateTagPages(BlogService $blogService, SeoService $seoService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        foreach ($blogService->getTags() as $tag) {
            try {
                $posts = $blogService->getPostsByTag($tag);
                $tagSlug = Str::slug($tag);
                $tagTitle = Str::title(str_replace('-', ' ', $tagSlug));
                $seo = $seoService->resolve([], $tagTitle);

                $html = view('pergament::blog.tag', [
                    'posts' => $posts,
                    'tag' => $tagTitle,
                    'tagSlug' => $tagSlug,
                    'seo' => $seo,
                ])->render();

                $path = $outputDir.'/'.$blogPrefix.'/tag/'.$tagSlug.'/index.html';
                $this->writeFile($path, $this->postProcessHtml($html));
            } catch (Throwable $e) {
                $this->errors[] = "Tag {$tag}: {$e->getMessage()}";
            }
        }
    }

    private function generateAuthorPages(BlogService $blogService, SeoService $seoService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        foreach ($blogService->getAuthors() as $author) {
            try {
                $posts = $blogService->getPostsByAuthor($author->slug());
                $seo = $seoService->resolve([], $author->name);

                $html = view('pergament::blog.author', [
                    'posts' => $posts,
                    'author' => $author->name,
                    'authorSlug' => $author->slug(),
                    'seo' => $seo,
                ])->render();

                $path = $outputDir.'/'.$blogPrefix.'/author/'.$author->slug().'/index.html';
                $this->writeFile($path, $this->postProcessHtml($html));
            } catch (Throwable $e) {
                $this->errors[] = "Author {$author->name}: {$e->getMessage()}";
            }
        }
    }

    private function generatePages(PageService $pageService, SeoService $seoService, string $outputDir): void
    {
        $homepageConfig = config('pergament.homepage', []);
        $homepageSlug = ($homepageConfig['type'] ?? '') === 'page' ? ($homepageConfig['source'] ?? 'home') : null;

        foreach ($pageService->getSlugs() as $slug) {
            if ($slug === $homepageSlug) {
                continue;
            }

            try {
                $page = $pageService->getRenderedPage($slug);

                if ($page === null) {
                    continue;
                }

                $this->collectLinkErrors($page);
                $seo = $seoService->resolve($page['meta'], $page['title']);
                $layout = $page['layout'] ?? 'default';

                $html = view('pergament::pages.show', [
                    'page' => $page,
                    'seo' => $seo,
                    'layout' => $layout,
                    'isHomepage' => false,
                ])->render();

                $path = $outputDir.'/'.$slug.'/index.html';
                $this->writeFile($path, $this->postProcessHtml($html));
            } catch (Throwable $e) {
                $this->errors[] = "Page {$slug}: {$e->getMessage()}";
            }
        }
    }

    private function generateFeed(FeedService $feedService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        try {
            $type = config('pergament.blog.feed.type', 'atom');
            $content = $type === 'rss' ? $feedService->rss() : $feedService->atom();

            $this->writeFile($outputDir.'/'.$blogPrefix.'/feed/index.xml', $content);
        } catch (Throwable $e) {
            $this->errors[] = "Feed: {$e->getMessage()}";
        }
    }

    private function generateSitemap(SitemapService $sitemapService, string $outputDir): void
    {
        try {
            $this->writeFile($outputDir.'/sitemap.xml', $sitemapService->generate());
        } catch (Throwable $e) {
            $this->errors[] = "Sitemap: {$e->getMessage()}";
        }
    }

    private function generateRobots(string $outputDir): void
    {
        try {
            $custom = config('pergament.robots.content');

            if ($custom !== null) {
                $this->writeFile($outputDir.'/robots.txt', $custom);

                return;
            }

            $lines = ['User-agent: *', 'Allow: /'];

            if (config('pergament.sitemap.enabled', true)) {
                $lines[] = '';
                $lines[] = 'Sitemap: '.UrlGenerator::url('sitemap.xml');
            }

            $this->writeFile($outputDir.'/robots.txt', implode("\n", $lines));
        } catch (Throwable $e) {
            $this->errors[] = "Robots: {$e->getMessage()}";
        }
    }

    private function generateLlms(string $outputDir): void
    {
        try {
            $custom = config('pergament.llms.content');

            if ($custom !== null) {
                $this->writeFile($outputDir.'/llms.txt', $custom);

                return;
            }

            $siteName = config('pergament.site.name', '');
            $description = config('pergament.site.seo.description', '');

            $lines = ['# '.$siteName];

            if ($description !== '') {
                $lines[] = '';
                $lines[] = '> '.$description;
            }

            $lines[] = '';
            $lines[] = '## Documentation';
            $lines[] = '';

            $docsPrefix = config('pergament.docs.url_prefix', 'docs');
            $lines[] = 'Documentation is available at '.UrlGenerator::url($docsPrefix);

            $this->writeFile($outputDir.'/llms.txt', implode("\n", $lines));
        } catch (Throwable $e) {
            $this->errors[] = "LLMs: {$e->getMessage()}";
        }
    }

    private function copyDocMedia(DocumentationService $docsService, string $outputDir): void
    {
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');
        $contentPath = config('pergament.content_path', 'content').'/'.config('pergament.docs.path', 'docs');

        if (! is_dir($contentPath)) {
            return;
        }

        foreach ($docsService->getChapters() as $chapter) {
            $chapterDir = $this->findNumberedDirectory($contentPath, $chapter->slug);

            if ($chapterDir === null) {
                continue;
            }

            foreach (scandir($chapterDir) as $file) {
                if ($file === '.' || $file === '..' || str_ends_with($file, '.md')) {
                    continue;
                }

                $filePath = $chapterDir.'/'.$file;

                if (! is_file($filePath)) {
                    continue;
                }

                $destPath = $outputDir.'/'.$docsPrefix.'/media/'.$chapter->slug.'/'.$file;
                $this->copyFile($filePath, $destPath);
            }
        }
    }

    private function copyBlogMedia(BlogService $blogService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');
        $contentPath = config('pergament.content_path', 'content').'/'.config('pergament.blog.path', 'blog');

        if (! is_dir($contentPath)) {
            return;
        }

        foreach ($blogService->getPosts() as $post) {
            $postDir = $this->findBlogPostDirectory($contentPath, $post->slug);

            if ($postDir === null) {
                continue;
            }

            foreach (scandir($postDir) as $file) {
                if ($file === '.' || $file === '..' || $file === 'post.md') {
                    continue;
                }

                $filePath = $postDir.'/'.$file;

                if (! is_file($filePath)) {
                    continue;
                }

                $destPath = $outputDir.'/'.$blogPrefix.'/media/'.$post->slug.'/'.$file;
                $this->copyFile($filePath, $destPath);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pageData
     */
    private function collectLinkErrors(array $pageData): void
    {
        if (! empty($pageData['linkErrors'])) {
            array_push($this->errors, ...$pageData['linkErrors']);
        }
    }

    private function postProcessHtml(string $html): string
    {
        $html = $this->rewritePaginationLinks($html);

        return $html;
    }

    private function rewritePaginationLinks(string $html): string
    {
        return (string) preg_replace_callback(
            '/(href=["\'])([^"\']*?)\?page=(\d+)(["\'])/',
            function (array $matches): string {
                $prefix = $matches[1];
                $basePath = $matches[2];
                $page = $matches[3];
                $suffix = $matches[4];

                $basePath = mb_rtrim($basePath, '/');

                return $prefix.$basePath.'/page/'.$page.'/'.$suffix;
            },
            $html,
        );
    }

    private function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function copyFile(string $source, string $destination): void
    {
        $dir = dirname($destination);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy($source, $destination);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($dir);
    }

    private function findNumberedDirectory(string $basePath, string $slug): ?string
    {
        foreach (scandir($basePath) as $entry) {
            if (! is_dir($basePath.'/'.$entry)) {
                continue;
            }

            if (preg_replace('/^\d+-/', '', $entry) === $slug) {
                return $basePath.'/'.$entry;
            }
        }

        return null;
    }

    private function findBlogPostDirectory(string $basePath, string $slug): ?string
    {
        foreach (scandir($basePath) as $entry) {
            if (! is_dir($basePath.'/'.$entry)) {
                continue;
            }

            if (preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $entry) === $slug) {
                return $basePath.'/'.$entry;
            }
        }

        return null;
    }
}
