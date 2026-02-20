@extends('pergament::layouts.app')

@section('seo')
    <x-pergament::seo-head :seo="$seo" />
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <article>
        {{-- Header --}}
        <header class="mb-8">
            @if($post['category'])
                <a href="{{ route('pergament.blog.category', \Illuminate\Support\Str::slug($post['category'])) }}" class="inline-block text-sm font-medium pergament-primary-link mb-3 transition-colors">
                    {{ $post['category'] }}
                </a>
            @endif

            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $post['title'] }}
            </h1>

            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <time datetime="{{ $post['date']->toDateString() }}">
                    {{ $post['date']->format('F j, Y') }}
                </time>

                @if(count($post['authors']) > 0)
                    <span class="flex items-center gap-1">
                        @foreach($post['authors'] as $author)
                            <a href="{{ route('pergament.blog.author', $author->slug()) }}" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                                {{ $author->name }}
                            </a>
                            @unless($loop->last)<span>,</span>@endunless
                        @endforeach
                    </span>
                @endif
            </div>

            @if(count($post['tags']) > 0)
                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach($post['tags'] as $tag)
                        <a href="{{ route('pergament.blog.tag', \Illuminate\Support\Str::slug($tag)) }}" class="inline-block text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            {{ $tag }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        {{-- Content --}}
        <div class="prose dark:prose-invert max-w-none">
            {!! $post['htmlContent'] !!}
        </div>

        {{-- Author section --}}
        @if(count($post['authors']) > 0)
            <div class="mt-12 border-t border-gray-200 dark:border-gray-700 pt-8">
                @foreach($post['authors'] as $author)
                    <div class="flex items-center gap-4 {{ !$loop->last ? 'mb-6' : '' }}">
                        <div class="flex-shrink-0 size-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            @if($author->avatar)
                                <img src="{{ $author->avatar }}" alt="{{ $author->name }}" class="size-12 rounded-full object-cover">
                            @else
                                <span class="text-lg font-semibold text-gray-500 dark:text-gray-400">
                                    {{ strtoupper(substr($author->name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('pergament.blog.author', $author->slug()) }}" class="font-medium text-gray-900 dark:text-white pergament-primary-hover transition-colors">
                                {{ $author->name }}
                            </a>
                            @if($author->url)
                                <a href="{{ $author->url }}" target="_blank" rel="noopener" class="block text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                    {{ parse_url($author->url, PHP_URL_HOST) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Previous / Next navigation --}}
        <nav class="mt-10 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6">
            @if($post['previousPost'])
                <a href="{{ $post['previousPost']['url'] }}" class="group flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="size-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    {{ $post['previousPost']['title'] }}
                </a>
            @else
                <span></span>
            @endif

            @if($post['nextPost'])
                <a href="{{ $post['nextPost']['url'] }}" class="group flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    {{ $post['nextPost']['title'] }}
                    <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            @endif
        </nav>
    </article>
</div>
@endsection
