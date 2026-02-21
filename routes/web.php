<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pergament\Http\Controllers\BlogController;
use Pergament\Http\Controllers\DocumentationController;
use Pergament\Http\Controllers\FeedController;
use Pergament\Http\Controllers\HomeController;
use Pergament\Http\Controllers\PageController;
use Pergament\Http\Controllers\PwaController;
use Pergament\Http\Controllers\RobotsController;
use Pergament\Http\Controllers\SearchController;
use Pergament\Http\Controllers\SitemapController;
use Pergament\Http\Middleware\MarkdownResponse;
use Pergament\Services\PageService;
use Pergament\Support\UrlGenerator;

// CSS asset — static file takes priority when vendor:published; this route is the fallback
Route::get('vendor/pergament/pergament.css', function () {
    $path = __DIR__.'/../dist/pergament.css';
    $content = file_get_contents($path);
    $etag = '"'.md5($content).'"';

    if (request()->header('If-None-Match') === $etag) {
        return response('', 304, [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    return response($content, 200, [
        'Content-Type' => 'text/css; charset=utf-8',
        'Cache-Control' => 'public, max-age=86400',
        'ETag' => $etag,
    ]);
})->name('pergament.css');

$basePrefix = UrlGenerator::basePrefix();

Route::prefix($basePrefix)->group(function (): void {

    // Sitemap
    if (config('pergament.sitemap.enabled', true)) {
        Route::get('sitemap.xml', SitemapController::class)->name('pergament.sitemap');
    }

    // Robots.txt
    if (config('pergament.robots.enabled', true)) {
        Route::get('robots.txt', [RobotsController::class, 'robots'])->name('pergament.robots');
    }

    // LLMs.txt
    if (config('pergament.llms.enabled', true)) {
        Route::get('llms.txt', [RobotsController::class, 'llms'])->name('pergament.llms');
    }

    // PWA
    if (config('pergament.pwa.enabled', false)) {
        Route::get('manifest.json', [PwaController::class, 'manifest'])->name('pergament.manifest');
        Route::get('sw.js', [PwaController::class, 'serviceWorker'])->name('pergament.sw');
    }

    // Search
    if (config('pergament.search.enabled', true)) {
        Route::get('search', SearchController::class)->name('pergament.search');
    }

    // Blog
    if (config('pergament.blog.enabled', true)) {
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        Route::prefix($blogPrefix)->name('pergament.blog.')->group(function (): void {
            // Feed and media are not HTML pages — skip markdown middleware
            if (config('pergament.blog.feed.enabled', true)) {
                Route::get('feed', FeedController::class)->name('feed');
            }

            Route::get('media/{slug}/{filename}', [BlogController::class, 'media'])
                ->where('filename', '.*')
                ->name('media');

            Route::get('/', [BlogController::class, 'index'])->name('index');
            // Content pages — serve as markdown when requested
            Route::middleware(MarkdownResponse::class)->group(function (): void {
                Route::get('category/{category}', [BlogController::class, 'category'])->name('category');
                Route::get('category/{category}.md', [BlogController::class, 'category'])->name('category.md');
                Route::get('tag/{tag}', [BlogController::class, 'tag'])->name('tag');
                Route::get('tag/{tag}.md', [BlogController::class, 'tag'])->name('tag.md');
                Route::get('author/{author}', [BlogController::class, 'author'])->name('author');
                Route::get('author/{author}.md', [BlogController::class, 'author'])->name('author.md');
                Route::get('{slug}', [BlogController::class, 'show'])->name('show');
                Route::get('{slug}.md', [BlogController::class, 'show'])->name('show.md');
            });
        });
    }

    // Documentation
    if (config('pergament.docs.enabled', true)) {
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        Route::prefix($docsPrefix)->name('pergament.docs.')->group(function (): void {
            // Media files are not HTML pages — skip markdown middleware
            Route::get('media/{path}', [DocumentationController::class, 'media'])
                ->where('path', '.*')
                ->name('media');

            Route::get('/', [DocumentationController::class, 'index'])->name('index');
            // Content pages — serve as markdown when requested
            Route::middleware(MarkdownResponse::class)->group(function (): void {
                Route::get('{chapter}/{page}', [DocumentationController::class, 'show'])->name('show');
                Route::get('{chapter}/{page}.md', [DocumentationController::class, 'show'])->name('show.md');
            });
        });
    }

    // Homepage and standalone pages — serve as markdown when requested
    Route::middleware(MarkdownResponse::class)->group(function (): void {
        Route::get('/', HomeController::class)->name('pergament.home');
        Route::get('/index.md', HomeController::class)->name('pergament.home.md');

        if (config('pergament.pages.enabled', true)) {
            /** @var PageService $pageService */
            $pageService = resolve(PageService::class);

            Route::get('{slug}', PageController::class)
                ->whereIn('slug', $pageService->getSlugs()->toArray())
                ->name('pergament.page');
        }
    });
});
