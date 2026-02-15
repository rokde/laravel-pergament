<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Str;
use Pergament\Support\UrlGenerator;

final class SitemapService
{
    public function __construct(
        private DocumentationService $docs,
        private BlogService $blog,
    ) {}

    /**
     * Generate a sitemap XML string with all discoverable URLs.
     */
    public function generate(): string
    {
        $urls = [];

        $urls[] = ['loc' => UrlGenerator::url(), 'priority' => '1.0'];

        if (config('pergament.docs.enabled', true)) {
            $docsPrefix = config('pergament.docs.url_prefix', 'docs');

            foreach ($this->docs->getChapters() as $chapter) {
                foreach ($chapter->pages as $page) {
                    $urls[] = [
                        'loc' => UrlGenerator::url($docsPrefix, $chapter->slug, $page->slug),
                        'priority' => '0.8',
                    ];
                }
            }
        }

        if (config('pergament.blog.enabled', true)) {
            $blogPrefix = config('pergament.blog.url_prefix', 'blog');
            $urls[] = ['loc' => UrlGenerator::url($blogPrefix), 'priority' => '0.7'];

            foreach ($this->blog->getPosts() as $post) {
                $urls[] = [
                    'loc' => UrlGenerator::url($blogPrefix, $post->slug),
                    'lastmod' => $post->date->toDateString(),
                    'priority' => '0.6',
                ];
            }

            foreach ($this->blog->getCategories() as $category) {
                $urls[] = [
                    'loc' => UrlGenerator::url($blogPrefix, 'category', Str::slug($category)),
                    'priority' => '0.5',
                ];
            }

            foreach ($this->blog->getTags() as $tag) {
                $urls[] = [
                    'loc' => UrlGenerator::url($blogPrefix, 'tag', Str::slug($tag)),
                    'priority' => '0.4',
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($url['loc']).'</loc>'."\n";
            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            }
            if (isset($url['priority'])) {
                $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            }
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
