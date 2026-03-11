<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Pergament\Services\PageService;
use Pergament\Services\SeoService;
use Pergament\Support\UrlGenerator;

final class PageController
{
    public function __invoke(Request $request, string $slug, PageService $pageService, SeoService $seoService): View|Response
    {
        if (str_ends_with($slug, '.md')) {
            $slug = substr($slug, 0, -3);
        }

        if ($request->attributes->get('pergament.wants_raw_markdown')) {
            $markdown = $pageService->getRawMarkdown($slug);
            abort_unless($markdown !== null, 404);

            return new Response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
        }

        $page = $pageService->getRenderedPage($slug);

        abort_unless($page !== null, 404);

        $canonicalUrl = UrlGenerator::url($slug);
        $seo = $seoService->resolve($page['meta'], $page['title'], $canonicalUrl);
        $layout = $page['layout'] ?? 'default';

        return view('pergament::pages.show', [
            'page' => $page,
            'seo' => $seo,
            'layout' => $layout,
            'isHomepage' => false,
        ]);
    }
}
