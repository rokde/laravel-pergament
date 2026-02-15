@extends('pergament::layouts.app')

@section('seo')
    <x-pergament::seo-head :seo="$seo" />
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
        {{ config('pergament.blog.title', 'Blog') }}
    </h1>

    <div class="lg:grid lg:grid-cols-12 lg:gap-10">
        {{-- Posts grid --}}
        <div class="lg:col-span-8">
            @if($posts->isEmpty())
                <p class="text-gray-500 dark:text-gray-400">No posts yet.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($posts as $post)
                        <x-pergament::post-card :post="$post" />
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($lastPage > 1)
                    <nav class="mt-10 flex items-center justify-between">
                        @if($currentPage > 1)
                            <a href="{{ route('pergament.blog.index', ['page' => $currentPage - 1]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                Previous
                            </a>
                        @else
                            <span></span>
                        @endif

                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Page {{ $currentPage }} of {{ $lastPage }}
                        </span>

                        @if($currentPage < $lastPage)
                            <a href="{{ route('pergament.blog.index', ['page' => $currentPage + 1]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                Next
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        @else
                            <span></span>
                        @endif
                    </nav>
                @endif
            @endif
        </div>

        {{-- Sidebar --}}
        <aside class="lg:col-span-4 mt-10 lg:mt-0">
            @if(isset($categories) && $categories->isNotEmpty())
                <div class="mb-8">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                        Categories
                    </h3>
                    <ul class="space-y-1.5">
                        @foreach($categories as $category)
                            <li>
                                <a href="{{ route('pergament.blog.category', \Illuminate\Support\Str::slug($category)) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    {{ $category }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(isset($tags) && $tags->isNotEmpty())
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                        Tags
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <a href="{{ route('pergament.blog.tag', \Illuminate\Support\Str::slug($tag)) }}" class="inline-block text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                {{ $tag }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
