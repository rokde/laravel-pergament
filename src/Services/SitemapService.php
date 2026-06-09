<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Str;
use Pergament\Support\UrlGenerator;

final readonly class SitemapService
{
    public function __construct(
        private DocumentationService $docs,
        private BlogService $blog,
    ) {}

    /**
     * Generate a sitemap XML string with all discoverable URLs.
     *
     * Pass $htmlExtension = true when generating for a static site where every
     * content page is written as a .html file (e.g. blog/hello-world.html).
     * Index/directory URLs (root, blog index) are intentionally left unchanged
     * because static hosts serve those from index.html automatically.
     */
    public function generate(bool $htmlExtension = false): string
    {
        $urls = [];

        $urls[] = ['loc' => UrlGenerator::url(), 'priority' => '1.0'];

        if (config('pergament.docs.enabled', true)) {
            $docsPrefix = config('pergament.docs.url_prefix', 'docs');

            foreach ($this->docs->getChapters() as $chapter) {
                foreach ($chapter->pages as $page) {
                    $loc = UrlGenerator::url($docsPrefix, $chapter->slug, $page->slug);
                    $urls[] = [
                        'loc' => $htmlExtension ? $loc.'.html' : $loc,
                        'priority' => '0.8',
                    ];
                }
            }
        }

        if (config('pergament.blog.enabled', true)) {
            $blogPrefix = config('pergament.blog.url_prefix', 'blog');
            $urls[] = ['loc' => UrlGenerator::url($blogPrefix), 'priority' => '0.7'];

            foreach ($this->blog->getPosts() as $post) {
                $loc = UrlGenerator::url($blogPrefix, $post->slug);
                $urls[] = [
                    'loc' => $htmlExtension ? $loc.'.html' : $loc,
                    'lastmod' => $post->date->toDateString(),
                    'priority' => '0.6',
                ];
            }

            foreach ($this->blog->getCategories() as $category) {
                $loc = UrlGenerator::url($blogPrefix, 'category', Str::slug($category));
                $urls[] = [
                    'loc' => $htmlExtension ? $loc.'.html' : $loc,
                    'priority' => '0.5',
                ];
            }

            foreach ($this->blog->getTags() as $tag) {
                $loc = UrlGenerator::url($blogPrefix, 'tag', Str::slug($tag));
                $urls[] = [
                    'loc' => $htmlExtension ? $loc.'.html' : $loc,
                    'priority' => '0.4',
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>'.e($url['loc']).'</loc>';
            if (isset($url['lastmod'])) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }
            if (isset($url['priority'])) {
                $xml .= '<priority>'.$url['priority'].'</priority>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
