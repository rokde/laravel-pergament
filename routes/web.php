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
use Pergament\Support\UrlGenerator;

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
            if (config('pergament.blog.feed.enabled', true)) {
                Route::get('feed', FeedController::class)->name('feed');
            }

            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::get('category/{category}', [BlogController::class, 'category'])->name('category');
            Route::get('tag/{tag}', [BlogController::class, 'tag'])->name('tag');
            Route::get('author/{author}', [BlogController::class, 'author'])->name('author');
            Route::get('media/{slug}/{filename}', [BlogController::class, 'media'])
                ->where('filename', '.*')
                ->name('media');
            Route::get('{slug}', [BlogController::class, 'show'])->name('show');
        });
    }

    // Documentation
    if (config('pergament.docs.enabled', true)) {
        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        Route::prefix($docsPrefix)->name('pergament.docs.')->group(function (): void {
            Route::get('/', [DocumentationController::class, 'index'])->name('index');
            Route::get('media/{path}', [DocumentationController::class, 'media'])
                ->where('path', '.*')
                ->name('media');
            Route::get('{chapter}/{page}', [DocumentationController::class, 'show'])->name('show');
        });
    }

    // Homepage (base of the Pergament prefix)
    Route::get('/', HomeController::class)->name('pergament.home');

    // Standalone pages (registered last as catch-all)
    if (config('pergament.pages.enabled', true)) {
        Route::get('{slug}', PageController::class)
            ->where('slug', '[a-z0-9\-]+')
            ->name('pergament.page');
    }
});
