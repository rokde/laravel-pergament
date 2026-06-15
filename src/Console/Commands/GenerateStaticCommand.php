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
use Pergament\Support\MarkdownExporter;
use Pergament\Support\PortableLinkRewriter;
use Pergament\Support\UrlGenerator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class GenerateStaticCommand extends Command
{
    protected $signature = 'pergament:generate-static
                            {output-dir : The directory to write static files to}
                            {--content-path= : Override the content source directory for this export}
                            {--prefix= : Override URL prefix for this export}
                            {--base-url= : Override site URL for sitemap/feed}
                            {--clean : Remove output directory before generating}';

    protected $description = 'Generate a self-contained static HTML site from Pergament content';

    /** @var array<int, string> */
    private array $errors = [];

    /** @var array<int, array{title: string, excerpt: string, content: string, url: string, type: string}> */
    private array $searchIndex = [];

    private PortableLinkRewriter $rewriter;

    private MarkdownExporter $exporter;

    public function handle(
        DocumentationService $docsService,
        BlogService $blogService,
        PageService $pageService,
        SitemapService $sitemapService,
        FeedService $feedService,
        SeoService $seoService,
    ): int {
        $outputDir = mb_rtrim((string) $this->argument('output-dir'), '/');

        $this->errors = [];
        $this->searchIndex = [];

        $originalPrefix = config('pergament.prefix');
        $originalSiteUrl = config('pergament.site.url');
        $originalContentPath = config('pergament.content_path');

        try {
            if ($this->option('content-path') !== null) {
                config()->set('pergament.content_path', mb_rtrim((string) $this->option('content-path'), '/'));
            }

            if ($this->option('prefix') !== null) {
                config()->set('pergament.prefix', $this->option('prefix'));
            }

            if ($this->option('base-url') !== null) {
                config()->set('pergament.site.url', $this->option('base-url'));
            }

            $this->rewriter = $this->makeRewriter();
            $this->exporter = new MarkdownExporter;

            if ($this->option('clean') && is_dir($outputDir)) {
                $this->removeDirectory($outputDir);
            }

            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $this->copyAssets($outputDir);
            $this->copyFavicon($outputDir);

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

            if (config('pergament.search.enabled', true)) {
                $this->generateSearchIndex($outputDir);
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
            config()->set('pergament.content_path', $originalContentPath);
        }
    }

    private function makeRewriter(): PortableLinkRewriter
    {
        $hosts = [];

        foreach ([config('app.url'), config('app.asset_url'), config('pergament.site.url')] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $host = parse_url($candidate, PHP_URL_HOST);

                if (is_string($host) && $host !== '') {
                    $hosts[] = $host;
                }
            }
        }

        return new PortableLinkRewriter(
            array_values(array_unique($hosts)),
            UrlGenerator::basePrefix(),
            (string) config('pergament.docs.url_prefix', 'docs'),
            (string) config('pergament.blog.url_prefix', 'blog'),
        );
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
            switch ($type) {
                case 'page':
                    $page = $pageService->getRenderedPage($source);

                    if ($page === null) {
                        return;
                    }

                    $this->collectLinkErrors($page);
                    $seo = $seoService->resolve($page['meta'], $page['title'], UrlGenerator::url());
                    $html = view('pergament::pages.show', [
                        'page' => $page,
                        'seo' => $seo,
                        'layout' => $page['layout'] ?? 'default',
                        'isHomepage' => true,
                    ])->render();

                    $this->writeContentPage($outputDir, 'index.html', $html, $page, $page['title'], 'page');

                    return;

                case 'doc-page':
                    $parts = explode('/', $source, 2);

                    if (count($parts) < 2) {
                        $first = $docsService->getFirstPage();

                        if ($first === null) {
                            return;
                        }

                        $parts = [$first['chapter'], $first['page']];
                    }

                    $page = $docsService->getRenderedPage($parts[0], $parts[1]);

                    if ($page === null) {
                        return;
                    }

                    $this->collectLinkErrors($page);
                    $seo = $seoService->resolve($page['meta'], $page['title'], UrlGenerator::url());
                    $html = view('pergament::docs.show', [
                        'page' => $page,
                        'navigation' => $docsService->getNavigation(),
                        'currentChapter' => $parts[0],
                        'currentPage' => $parts[1],
                        'seo' => $seo,
                    ])->render();

                    $this->writeContentPage($outputDir, 'index.html', $html, $page, $page['title'], 'doc');

                    return;

                case 'blog-index':
                    $this->writeListingPage($outputDir, 'index.html', $this->renderHomepageBlogIndex($blogService, $seoService));

                    return;

                case 'redirect':
                    $target = $this->rewriter->resolve($source, 'index.html');
                    $this->writeFile($outputDir.'/index.html', $this->renderRedirect($target));

                    return;
            }
        } catch (Throwable $e) {
            $this->errors[] = "Homepage: {$e->getMessage()}";
        }
    }

    private function renderHomepageBlogIndex(BlogService $blogService, SeoService $seoService): string
    {
        $paginated = $blogService->paginate(1);
        $canonicalUrl = UrlGenerator::url();
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'), $canonicalUrl);

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
            $absoluteTarget = UrlGenerator::path($docsPrefix, $first['chapter'], $first['page']);
            $relPath = $docsPrefix.'/index.html';
            $target = $this->rewriter->resolve($absoluteTarget, $relPath);

            $this->writeFile($outputDir.'/'.$relPath, $this->renderRedirect($target));
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
                    $canonicalUrl = UrlGenerator::url($docsPrefix, $chapter->slug, $page->slug).'.html';
                    $seo = $seoService->resolve($pageData['meta'], $pageData['title'], $canonicalUrl);

                    $html = view('pergament::docs.show', [
                        'page' => $pageData,
                        'navigation' => $docsService->getNavigation(),
                        'currentChapter' => $chapter->slug,
                        'currentPage' => $page->slug,
                        'seo' => $seo,
                    ])->render();

                    $relPath = $docsPrefix.'/'.$chapter->slug.'/'.$page->slug.'.html';
                    $this->writeContentPage($outputDir, $relPath, $html, $pageData, $pageData['title'], 'doc');
                } catch (Throwable $e) {
                    $this->errors[] = "Doc page {$chapter->slug}/{$page->slug}: {$e->getMessage()}";
                }
            }
        }
    }

    private function generateBlogIndex(BlogService $blogService, SeoService $seoService, string $outputDir): void
    {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');
        $canonicalUrl = UrlGenerator::url($blogPrefix);
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'), $canonicalUrl);
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

                if ($page === 1) {
                    $this->writeListingPage($outputDir, $blogPrefix.'/index.html', $html);
                }

                $this->writeListingPage($outputDir, $blogPrefix.'/page/'.$page.'.html', $html);
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
                $canonicalUrl = UrlGenerator::url($blogPrefix, $post->slug).'.html';
                $seo = $seoService->resolve($rendered['meta'], $rendered['title'], $canonicalUrl);

                $html = view('pergament::blog.show', [
                    'post' => $rendered,
                    'seo' => $seo,
                ])->render();

                $relPath = $blogPrefix.'/'.$post->slug.'.html';
                $this->writeContentPage($outputDir, $relPath, $html, $rendered, $rendered['title'], 'post');
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
                $canonicalUrl = UrlGenerator::url($blogPrefix, 'category', $categorySlug).'.html';
                $seo = $seoService->resolve([], $categoryTitle, $canonicalUrl);

                $html = view('pergament::blog.category', [
                    'posts' => $posts,
                    'category' => $categoryTitle,
                    'categorySlug' => $categorySlug,
                    'seo' => $seo,
                ])->render();

                $this->writeListingPage($outputDir, $blogPrefix.'/category/'.$categorySlug.'.html', $html);
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
                $canonicalUrl = UrlGenerator::url($blogPrefix, 'tag', $tagSlug).'.html';
                $seo = $seoService->resolve([], $tagTitle, $canonicalUrl);

                $html = view('pergament::blog.tag', [
                    'posts' => $posts,
                    'tag' => $tagTitle,
                    'tagSlug' => $tagSlug,
                    'seo' => $seo,
                ])->render();

                $this->writeListingPage($outputDir, $blogPrefix.'/tag/'.$tagSlug.'.html', $html);
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
                $canonicalUrl = UrlGenerator::url($blogPrefix, 'author', $author->slug()).'.html';
                $seo = $seoService->resolve([], $author->name, $canonicalUrl);

                $html = view('pergament::blog.author', [
                    'posts' => $posts,
                    'author' => $author->name,
                    'authorSlug' => $author->slug(),
                    'seo' => $seo,
                ])->render();

                $this->writeListingPage($outputDir, $blogPrefix.'/author/'.$author->slug().'.html', $html);
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
                $canonicalUrl = UrlGenerator::url($slug).'.html';
                $seo = $seoService->resolve($page['meta'], $page['title'], $canonicalUrl);

                $html = view('pergament::pages.show', [
                    'page' => $page,
                    'seo' => $seo,
                    'layout' => $page['layout'] ?? 'default',
                    'isHomepage' => false,
                ])->render();

                $this->writeContentPage($outputDir, $slug.'.html', $html, $page, $page['title'], 'page');
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

            $this->writeFile($outputDir.'/'.$blogPrefix.'/feed.xml', $content);
        } catch (Throwable $e) {
            $this->errors[] = "Feed: {$e->getMessage()}";
        }
    }

    private function generateSitemap(SitemapService $sitemapService, string $outputDir): void
    {
        try {
            $this->writeFile($outputDir.'/sitemap.xml', $sitemapService->generate(htmlExtension: true));
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

    private function copyAssets(string $outputDir): void
    {
        $packageRoot = dirname(__DIR__, 3);
        $distPath = $packageRoot.'/dist';
        $fontsPath = $packageRoot.'/resources/fonts';
        $assetsDir = $outputDir.'/assets';

        try {
            $css = $distPath.'/pergament.css';

            if (is_file($css)) {
                // Fonts are bundled alongside the stylesheet, so make their URLs relative.
                $contents = str_replace('/vendor/pergament/fonts/', 'fonts/', (string) file_get_contents($css));
                $this->writeFile($assetsDir.'/pergament.css', $contents);
            }

            $js = $distPath.'/pergament.js';

            if (is_file($js)) {
                $this->copyFile($js, $assetsDir.'/pergament.js');
            }

            if (is_dir($fontsPath)) {
                foreach (scandir($fontsPath) as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $src = $fontsPath.'/'.$file;

                    if (is_file($src)) {
                        $this->copyFile($src, $assetsDir.'/fonts/'.$file);
                    }
                }
            }
        } catch (Throwable $e) {
            $this->errors[] = "Assets: {$e->getMessage()}";
        }
    }

    private function copyFavicon(string $outputDir): void
    {
        $favicon = config('pergament.favicon');

        if (! is_string($favicon) || $favicon === '' || Str::startsWith($favicon, ['http://', 'https://', '//'])) {
            return;
        }

        try {
            $src = config('pergament.content_path').'/'.ltrim($favicon, '/');

            if (is_file($src)) {
                $this->copyFile($src, $outputDir.'/'.basename($favicon));
            } else {
                $this->errors[] = "Favicon: file not found at {$src}";
            }
        } catch (Throwable $e) {
            $this->errors[] = "Favicon: {$e->getMessage()}";
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

    /**
     * Write a content page as both a portable HTML file and a token-safe Markdown sidecar,
     * and record it in the client-side search index.
     *
     * @param  array<string, mixed>  $pageData
     */
    private function writeContentPage(string $outputDir, string $relHtmlPath, string $fullHtml, array $pageData, string $title, string $type): void
    {
        $this->writeFile($outputDir.'/'.$relHtmlPath, $this->finalizeHtml($fullHtml, $relHtmlPath));

        $relMdPath = preg_replace('/\.html$/', '.md', $relHtmlPath);
        $this->writeFile($outputDir.'/'.$relMdPath, $this->renderMarkdown($pageData, $title, $relMdPath));

        $this->searchIndex[] = [
            'title' => $title,
            'excerpt' => (string) ($pageData['excerpt'] ?? ''),
            'content' => $this->plainText((string) ($pageData['htmlContent'] ?? '')),
            'url' => $relHtmlPath,
            'type' => $type,
        ];
    }

    /**
     * Write an aggregated listing page (index, category, tag, author) as portable HTML only.
     */
    private function writeListingPage(string $outputDir, string $relHtmlPath, string $fullHtml): void
    {
        $this->writeFile($outputDir.'/'.$relHtmlPath, $this->finalizeHtml($fullHtml, $relHtmlPath));
    }

    /**
     * Make a rendered page portable: relativize links, then point runtime config (search index,
     * service worker) at static-friendly locations relative to the current page.
     */
    private function finalizeHtml(string $fullHtml, string $relHtmlPath): string
    {
        return $this->rewriteRuntimeConfig($this->rewriter->rewriteHtml($fullHtml, $relHtmlPath), $relHtmlPath);
    }

    private function rewriteRuntimeConfig(string $html, string $relHtmlPath): string
    {
        // The service worker needs a live server scope; disable it for the static export.
        $html = (string) preg_replace('/(\bswUrl\b\s*:\s*)"[^"]*"/', '${1}null', $html);

        if (config('pergament.search.enabled', true)) {
            $relSearch = $this->rewriter->resolve('/search.json', $relHtmlPath);
            $html = (string) preg_replace('/(\bsearchUrl\b\s*:\s*)"[^"]*"/', '${1}'.json_encode($relSearch), $html);
            $html = $this->rewriteSearchForms($html);
        }

        return $html;
    }

    private function rewriteSearchForms(string $html): string
    {
        $searchRoute = route('pergament.search');
        $searchPath = parse_url($searchRoute, PHP_URL_PATH);

        return (string) preg_replace_callback(
            '/<form\b[^>]*\baction=(["\'])(.*?)\1[^>]*>/i',
            static function (array $matches) use ($searchRoute, $searchPath): string {
                $action = html_entity_decode(trim($matches[2]), ENT_QUOTES);
                $actionPath = parse_url($action, PHP_URL_PATH);

                $matchesSearchRoute = $action === $searchRoute
                    || ($actionPath !== false && $searchPath !== false && $actionPath === $searchPath);

                if (! $matchesSearchRoute) {
                    return $matches[0];
                }

                $tag = preg_replace('/\baction=(["\'])(.*?)\1/i', 'action="#"', $matches[0], 1);

                if ($tag === null || str_contains($tag, 'data-pergament-static-search=')) {
                    return $matches[0];
                }

                return preg_replace('/<form\b/i', '<form data-pergament-static-search="true"', $tag, 1) ?? $matches[0];
            },
            $html,
        );
    }

    private function generateSearchIndex(string $outputDir): void
    {
        try {
            $this->writeFile(
                $outputDir.'/search.json',
                (string) json_encode($this->searchIndex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            );
        } catch (Throwable $e) {
            $this->errors[] = "Search index: {$e->getMessage()}";
        }
    }

    private function plainText(string $html): string
    {
        $text = mb_trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));

        return mb_substr($text, 0, 2000);
    }

    /**
     * @param  array<string, mixed>  $pageData
     */
    private function renderMarkdown(array $pageData, string $title, string $relMdPath): string
    {
        $fragment = $this->rewriter->rewriteHtml((string) ($pageData['htmlContent'] ?? ''), $relMdPath, 'md');

        return $this->exporter->fromHtml($fragment, $title);
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
