<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Pergament\Services\BlogService;
use Pergament\Services\SeoService;

final class BlogController
{
    public function index(Request $request, BlogService $service, SeoService $seoService): View
    {
        $page = (int) $request->query('page', '1');
        $paginated = $service->paginate($page);
        $seo = $seoService->resolve([], config('pergament.blog.title', 'Blog'));

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
        $post = $service->getRenderedPost($slug);

        abort_unless($post !== null, 404);

        $seo = $seoService->resolve($post['meta'], $post['title']);

        return view('pergament::blog.show', [
            'post' => $post,
            'seo' => $seo,
        ]);
    }

    public function category(string $category, BlogService $service, SeoService $seoService): View
    {
        $posts = $service->getPostsByCategory($category);
        $categoryTitle = Str::title(str_replace('-', ' ', $category));
        $seo = $seoService->resolve([], $categoryTitle);

        return view('pergament::blog.category', [
            'posts' => $posts,
            'category' => $categoryTitle,
            'categorySlug' => $category,
            'seo' => $seo,
        ]);
    }

    public function tag(string $tag, BlogService $service, SeoService $seoService): View
    {
        $posts = $service->getPostsByTag($tag);
        $tagTitle = Str::title(str_replace('-', ' ', $tag));
        $seo = $seoService->resolve([], $tagTitle);

        return view('pergament::blog.tag', [
            'posts' => $posts,
            'tag' => $tagTitle,
            'tagSlug' => $tag,
            'seo' => $seo,
        ]);
    }

    public function author(string $author, BlogService $service, SeoService $seoService): View
    {
        $posts = $service->getPostsByAuthor($author);
        $authorName = $posts->isNotEmpty()
            ? collect($posts->first()->authors)->first(fn ($a) => $a->slug() === $author)?->name ?? Str::title(str_replace('-', ' ', $author))
            : Str::title(str_replace('-', ' ', $author));

        $seo = $seoService->resolve([], $authorName);

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
