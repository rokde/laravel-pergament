<?php

declare(strict_types=1);

namespace Pergament\Services;

use Pergament\Data\SeoMeta;
use Pergament\Support\FrontMatterParser;

final readonly class SeoService
{
    public function __construct(
        private FrontMatterParser $parser,
    ) {}

    /**
     * Build SEO metadata by merging site defaults with page-specific overrides.
     *
     * @param  array<string, mixed>  $pageMeta
     */
    public function resolve(array $pageMeta = [], ?string $pageTitle = null): SeoMeta
    {
        $siteConfig = config('pergament.site', []);
        $defaults = $siteConfig['seo'] ?? [];

        $seoOverrides = [];
        foreach ($pageMeta as $key => $value) {
            if (str_starts_with($key, 'seo.')) {
                $seoOverrides[mb_substr($key, 4)] = $value;
            }
        }

        $merged = $this->parser->mergeWithDotNotation($defaults, $seoOverrides);

        $title = $merged['title'] ?? $pageTitle ?? $siteConfig['name'] ?? '';
        if ($pageTitle !== null && ! isset($seoOverrides['title'])) {
            $siteName = $siteConfig['name'] ?? '';
            $title = $pageTitle.($siteName !== '' ? ' - '.$siteName : '');
        }

        return new SeoMeta(
            title: (string) $title,
            description: (string) ($merged['description'] ?? ''),
            keywords: (string) ($merged['keywords'] ?? ''),
            ogImage: (string) ($merged['og_image'] ?? ''),
            twitterCard: (string) ($merged['twitter_card'] ?? 'summary_large_image'),
            robots: (string) ($merged['robots'] ?? 'index, follow'),
            canonical: isset($pageMeta['seo.canonical']) ? (string) $pageMeta['seo.canonical'] : null,
            ogType: isset($pageMeta['seo.og_type']) ? (string) $pageMeta['seo.og_type'] : null,
        );
    }
}
