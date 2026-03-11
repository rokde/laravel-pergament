<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Pergament\Services\BlogService;
use Pergament\Services\SeoService;
use Pergament\Support\UrlGenerator;

final class BlogController
{
    public function index(Request $request, BlogService $service, SeoService $seoService): View
    {
        $page = (int) $request->query('page', '1');
        $paginated = $service->paginate($page);
        $canonicalUrl = UrlGenerator::url(config('pergament.blog.url_prefix', 'blog'));
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'), $canonicalUrl);

        return view('pergament::blog.index', [
            'posts' => $paginated['posts'],
            'currentPage' => $paginated['currentPage'],
            'lastPage' => $paginated['lastPage'],
            'total' => $paginated['total'],
            'categories' => $service->getCategories(),
            'tags' => $service->getTags(),
            'seo' => $seo,
        ]);
    }

    public function show(string $slug, BlogService $service, SeoService $seoService): View
    {
        if (str_ends_with($slug, '.md')) {
            $slug = substr($slug, 0, -3);
        }

        $post = $service->getRenderedPost($slug);

        abort_unless($post !== null, 404);

        $canonicalUrl = UrlGenerator::url(config('pergament.blog.url_prefix', 'blog'), $slug);
        $seo = $seoService->resolve($post['meta'], $post['title'], $canonicalUrl);

        return view('pergament::blog.show', [
            'post' => $post,
            'seo' => $seo,
        ]);
    }

    public function category(string $category, BlogService $service, SeoService $seoService): View
    {
        if (str_ends_with($category, '.md')) {
            $category = substr($category, 0, -3);
        }

        $posts = $service->getPostsByCategory($category);
        $categoryTitle = Str::title(str_replace('-', ' ', $category));
        $canonicalUrl = UrlGenerator::url(config('pergament.blog.url_prefix', 'blog'), 'category', $category);
        $seo = $seoService->resolve([], $categoryTitle, $canonicalUrl);

        return view('pergament::blog.category', [
            'posts' => $posts,
            'category' => $categoryTitle,
            'categorySlug' => $category,
            'seo' => $seo,
        ]);
    }

    public function tag(string $tag, BlogService $service, SeoService $seoService): View
    {
        if (str_ends_with($tag, '.md')) {
            $tag = substr($tag, 0, -3);
        }

        $posts = $service->getPostsByTag($tag);
        $tagTitle = Str::title(str_replace('-', ' ', $tag));
        $canonicalUrl = UrlGenerator::url(config('pergament.blog.url_prefix', 'blog'), 'tag', $tag);
        $seo = $seoService->resolve([], $tagTitle, $canonicalUrl);

        return view('pergament::blog.tag', [
            'posts' => $posts,
            'tag' => $tagTitle,
            'tagSlug' => $tag,
            'seo' => $seo,
        ]);
    }

    public function author(string $author, BlogService $service, SeoService $seoService): View
    {
        if (str_ends_with($author, '.md')) {
            $author = substr($author, 0, -3);
        }

        $posts = $service->getPostsByAuthor($author);
        $authorName = $posts->isNotEmpty()
            ? collect($posts->first()->authors)->first(fn ($a) => $a->slug() === $author)?->name ?? Str::title(str_replace('-', ' ', $author))
            : Str::title(str_replace('-', ' ', $author));

        $canonicalUrl = UrlGenerator::url(config('pergament.blog.url_prefix', 'blog'), 'author', $author);
        $seo = $seoService->resolve([], $authorName, $canonicalUrl);

        return view('pergament::blog.author', [
            'posts' => $posts,
            'author' => $authorName,
            'authorSlug' => $author,
            'seo' => $seo,
        ]);
    }

    public function media(string $slug, string $filename, BlogService $service): Response
    {
        $filePath = $service->resolveMediaPath($slug, $filename);

        abort_unless($filePath !== null, 404);

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        return response(file_get_contents($filePath), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
