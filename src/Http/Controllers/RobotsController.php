<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Response;
use Pergament\Support\UrlGenerator;

final class RobotsController
{
    public function robots(): Response
    {
        $custom = config('pergament.robots.content');

        if ($custom !== null) {
            return $this->textResponse($custom);
        }

        $lines = ['User-agent: *', 'Allow: /'];

        if (config('pergament.sitemap.enabled', true)) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.UrlGenerator::url('sitemap.xml');
        }

        return $this->textResponse(implode("\n", $lines));
    }

    public function llms(): Response
    {
        $custom = config('pergament.llms.content');

        if ($custom !== null) {
            return $this->textResponse($custom);
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

        return $this->textResponse(implode("\n", $lines));
    }

    private function textResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
