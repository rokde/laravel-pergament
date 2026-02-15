<?php

declare(strict_types=1);

namespace Pergament\Services;

use Pergament\Support\UrlGenerator;

final class FeedService
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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";
        $xml .= '  <title>'.e($feedTitle).'</title>'."\n";
        $xml .= '  <subtitle>'.e($feedDescription).'</subtitle>'."\n";
        $xml .= '  <link href="'.e(UrlGenerator::url($blogPrefix, 'feed')).'" rel="self" type="application/atom+xml"/>'."\n";
        $xml .= '  <link href="'.e(UrlGenerator::url($blogPrefix)).'" rel="alternate" type="text/html"/>'."\n";
        $xml .= '  <id>'.e(UrlGenerator::url($blogPrefix)).'</id>'."\n";
        $xml .= '  <updated>'.$updated.'</updated>'."\n";

        foreach ($posts as $post) {
            $postUrl = UrlGenerator::url($blogPrefix, $post->slug);
            $xml .= '  <entry>'."\n";
            $xml .= '    <title>'.e($post->title).'</title>'."\n";
            $xml .= '    <link href="'.e($postUrl).'" rel="alternate" type="text/html"/>'."\n";
            $xml .= '    <id>'.e($postUrl).'</id>'."\n";
            $xml .= '    <published>'.$post->date->toAtomString().'</published>'."\n";
            $xml .= '    <updated>'.$post->date->toAtomString().'</updated>'."\n";
            $xml .= '    <summary>'.e($post->excerpt).'</summary>'."\n";

            foreach ($post->authors as $author) {
                $xml .= '    <author>'."\n";
                $xml .= '      <name>'.e($author->name).'</name>'."\n";
                if ($author->email !== null) {
                    $xml .= '      <email>'.e($author->email).'</email>'."\n";
                }
                $xml .= '    </author>'."\n";
            }

            if ($post->category !== null) {
                $xml .= '    <category term="'.e($post->category).'"/>'."\n";
            }

            $xml .= '  </entry>'."\n";
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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
        $xml .= '  <channel>'."\n";
        $xml .= '    <title>'.e($feedTitle).'</title>'."\n";
        $xml .= '    <link>'.e(UrlGenerator::url($blogPrefix)).'</link>'."\n";
        $xml .= '    <description>'.e($feedDescription).'</description>'."\n";
        $xml .= '    <atom:link href="'.e(UrlGenerator::url($blogPrefix, 'feed')).'" rel="self" type="application/rss+xml"/>'."\n";

        if ($posts->isNotEmpty()) {
            $xml .= '    <lastBuildDate>'.$posts->first()->date->toRssString().'</lastBuildDate>'."\n";
        }

        foreach ($posts as $post) {
            $postUrl = UrlGenerator::url($blogPrefix, $post->slug);
            $xml .= '    <item>'."\n";
            $xml .= '      <title>'.e($post->title).'</title>'."\n";
            $xml .= '      <link>'.e($postUrl).'</link>'."\n";
            $xml .= '      <guid isPermaLink="true">'.e($postUrl).'</guid>'."\n";
            $xml .= '      <pubDate>'.$post->date->toRssString().'</pubDate>'."\n";
            $xml .= '      <description>'.e($post->excerpt).'</description>'."\n";

            foreach ($post->authors as $author) {
                $authorStr = $author->email !== null ? $author->email.' ('.$author->name.')' : $author->name;
                $xml .= '      <author>'.e($authorStr).'</author>'."\n";
            }

            if ($post->category !== null) {
                $xml .= '      <category>'.e($post->category).'</category>'."\n";
            }

            $xml .= '    </item>'."\n";
        }

        $xml .= '  </channel>'."\n";
        $xml .= '</rss>';

        return $xml;
    }
}
