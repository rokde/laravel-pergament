<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Pergament\Services\DocumentationService;
use Pergament\Services\SeoService;
use Pergament\Support\UrlGenerator;

final class DocumentationController
{
    public function index(DocumentationService $service): RedirectResponse
    {
        $first = $service->getFirstPage();

        if ($first === null) {
            abort(404);
        }

        $docsPrefix = config('pergament.docs.url_prefix', 'docs');

        return redirect(UrlGenerator::path($docsPrefix, $first['chapter'], $first['page']));
    }

    public function show(
        string $chapter,
        string $page,
        DocumentationService $service,
        SeoService $seoService,
    ): View {
        $pageData = $service->getRenderedPage($chapter, $page);

        abort_unless($pageData !== null, 404);

        $seo = $seoService->resolve($pageData['meta'], $pageData['title']);

        return view('pergament::docs.show', [
            'page' => $pageData,
            'navigation' => $service->getNavigation(),
            'currentChapter' => $chapter,
            'currentPage' => $page,
            'seo' => $seo,
        ]);
    }

    public function media(string $path, DocumentationService $service): Response
    {
        $filePath = $service->resolveMediaPath($path);

        abort_unless($filePath !== null, 404);

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        return response(file_get_contents($filePath), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
