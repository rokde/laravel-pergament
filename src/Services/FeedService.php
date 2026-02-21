<?php

declare(strict_types=1);

namespace Pergament\Services;

use Pergament\Support\UrlGenerator;

final readonly class FeedService
{
    public function __construct(
        private BlogService $blog,
    ) {}

    /**
     * Generate an Atom feed XML string.
     */
    public function atom(): string
    {
        $posts = $this->blog->getPosts()->take((int) config('pergament.blog.feed.limit', 20));
        $feedTitle = config('pergament.blog.feed.title') ?? config('pergament.site.name', 'Blog');
        $feedDescription = config('pergament.blog.feed.description', '');
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        $updated = $posts->isNotEmpty() ? $posts->first()->date->toAtomString() : now()->toAtomString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">';
        $xml .= '<title>'.e($feedTitle).'</title>';
        $xml .= '<subtitle>'.e($feedDescription).'</subtitle>';
        $xml .= '<link href="'.e(UrlGenerator::url($blogPrefix, 'feed')).'" rel="self" type="application/atom+xml"/>';
        $xml .= '<link href="'.e(UrlGenerator::url($blogPrefix)).'" rel="alternate" type="text/html"/>';
        $xml .= '<id>'.e(UrlGenerator::url($blogPrefix)).'</id>';
        $xml .= '<updated>'.$updated.'</updated>';

        foreach ($posts as $post) {
            $postUrl = UrlGenerator::url($blogPrefix, $post->slug);
            $xml .= '<entry>';
            $xml .= '<title>'.e($post->title).'</title>';
            $xml .= '<link href="'.e($postUrl).'" rel="alternate" type="text/html"/>';
            $xml .= '<id>'.e($postUrl).'</id>';
            $xml .= '<published>'.$post->date->toAtomString().'</published>';
            $xml .= '<updated>'.$post->date->toAtomString().'</updated>';
            $xml .= '<summary>'.e($post->excerpt).'</summary>';

            foreach ($post->authors as $author) {
                $xml .= '<author>';
                $xml .= '<name>'.e($author->name).'</name>';
                if ($author->email !== null) {
                    $xml .= '<email>'.e($author->email).'</email>';
                }
                $xml .= '</author>';
            }

            if ($post->category !== null) {
                $xml .= '<category term="'.e($post->category).'"/>';
            }

            $xml .= '</entry>';
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Generate an RSS 2.0 feed XML string.
     */
    public function rss(): string
    {
        $posts = $this->blog->getPosts()->take((int) config('pergament.blog.feed.limit', 20));
        $feedTitle = config('pergament.blog.feed.title') ?? config('pergament.site.name', 'Blog');
        $feedDescription = config('pergament.blog.feed.description', '');
        $blogPrefix = config('pergament.blog.url_prefix', 'blog');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
        $xml .= '<channel>';
        $xml .= '<title>'.e($feedTitle).'</title>';
        $xml .= '<link>'.e(UrlGenerator::url($blogPrefix)).'</link>';
        $xml .= '<description>'.e($feedDescription).'</description>';
        $xml .= '<atom:link href="'.e(UrlGenerator::url($blogPrefix, 'feed')).'" rel="self" type="application/rss+xml"/>';

        if ($posts->isNotEmpty()) {
            $xml .= '<lastBuildDate>'.$posts->first()->date->toRssString().'</lastBuildDate>';
        }

        foreach ($posts as $post) {
            $postUrl = UrlGenerator::url($blogPrefix, $post->slug);
            $xml .= '<item>';
            $xml .= '<title>'.e($post->title).'</title>';
            $xml .= '<link>'.e($postUrl).'</link>';
            $xml .= '<guid isPermaLink="true">'.e($postUrl).'</guid>';
            $xml .= '<pubDate>'.$post->date->toRssString().'</pubDate>';
            $xml .= '<description>'.e($post->excerpt).'</description>';

            foreach ($post->authors as $author) {
                $authorStr = $author->email !== null ? $author->email.' ('.$author->name.')' : $author->name;
                $xml .= '<author>'.e($authorStr).'</author>';
            }

            if ($post->category !== null) {
                $xml .= '<category>'.e($post->category).'</category>';
            }

            $xml .= '</item>';
        }

        $xml .= '</channel>';
        $xml .= '</rss>';

        return $xml;
    }
}
