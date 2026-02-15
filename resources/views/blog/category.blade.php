@extends('pergament::layouts.app')

@section('seo')
    <x-pergament::seo-head :seo="$seo" />
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="{{ route('pergament.blog.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
            &larr; Back to {{ config('pergament.blog.title', 'Blog') }}
        </a>
    </div>

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        {{ $category }}
    </h1>
    <p class="text-gray-500 dark:text-gray-400 mb-8">
        {{ $posts->count() }} {{ Str::plural('post', $posts->count()) }} in this category
    </p>

    @if($posts->isEmpty())
        <p class="text-gray-500 dark:text-gray-400">No posts found in this category.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <x-pergament::post-card :post="$post" />
            @endforeach
        </div>
    @endif
</div>
@endsection
