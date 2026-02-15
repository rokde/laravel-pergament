<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Pergament\Services\BlogService;
use Pergament\Services\DocumentationService;
use Pergament\Services\PageService;
use Pergament\Services\SeoService;

final class HomeController
{
    public function __invoke(
        PageService $pageService,
        DocumentationService $docsService,
        BlogService $blogService,
        SeoService $seoService,
    ): View|RedirectResponse|Response {
        $homepage = config('pergament.homepage', []);
        $type = $homepage['type'] ?? 'page';
        $source = $homepage['source'] ?? 'home';

        return match ($type) {
            'page' => $this->renderPage($pageService, $seoService, $source),
            'doc-page' => $this->renderDocPage($docsService, $seoService, $source),
            'blog-index' => $this->renderBlogIndex($blogService, $seoService),
            'redirect' => redirect($source),
            default => abort(404),
        };
    }

    private function renderPage(PageService $pageService, SeoService $seoService, string $slug): View
    {
        $page = $pageService->getRenderedPage($slug);

        abort_unless($page !== null, 404);

        $seo = $seoService->resolve($page['meta'], $page['title']);
        $layout = $page['layout'] ?? 'default';

        return view('pergament::pages.show', [
            'page' => $page,
            'seo' => $seo,
            'layout' => $layout,
            'isHomepage' => true,
        ]);
    }

    private function renderDocPage(DocumentationService $docsService, SeoService $seoService, string $source): View
    {
        $parts = explode('/', $source, 2);

        if (count($parts) < 2) {
            $first = $docsService->getFirstPage();
            abort_unless($first !== null, 404);
            $parts = [$first['chapter'], $first['page']];
        }

        $page = $docsService->getRenderedPage($parts[0], $parts[1]);
        abort_unless($page !== null, 404);

        $seo = $seoService->resolve($page['meta'], $page['title']);

        return view('pergament::docs.show', [
            'page' => $page,
            'navigation' => $docsService->getNavigation(),
            'currentChapter' => $parts[0],
            'currentPage' => $parts[1],
            'seo' => $seo,
        ]);
    }

    private function renderBlogIndex(BlogService $blogService, SeoService $seoService): View
    {
        $paginated = $blogService->paginate(1);
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'));

        return view('pergament::blog.index', [
            'posts' => $paginated['posts'],
            'currentPage' => $paginated['currentPage'],
            'lastPage' => $paginated['lastPage'],
            'total' => $paginated['total'],
            'seo' => $seo,
        ]);
    }
}
