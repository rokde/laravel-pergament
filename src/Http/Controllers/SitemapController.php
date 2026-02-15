<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Response;
use Pergament\Services\SitemapService;

final class SitemapController
{
    public function __invoke(SitemapService $sitemapService): Response
    {
        return response($sitemapService->generate(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
