<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\View\View;
use Pergament\Services\PageService;
use Pergament\Services\SeoService;

final class PageController
{
    public function __invoke(string $slug, PageService $pageService, SeoService $seoService): View
    {
        if (str_ends_with($slug, '.md')) {
            $slug = substr($slug, 0, -3);
        }

        $page = $pageService->getRenderedPage($slug);

        abort_unless($page !== null, 404);

        $seo = $seoService->resolve($page['meta'], $page['title']);
        $layout = $page['layout'] ?? 'default';

        return view('pergament::pages.show', [
            'page' => $page,
            'seo' => $seo,
            'layout' => $layout,
            'isHomepage' => false,
        ]);
    }
}
