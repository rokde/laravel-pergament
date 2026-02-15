@props(['post'])
@php /** @var \Pergament\Data\BlogPost $post */ @endphp

<article class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
    <div class="p-6">
        {{-- Category badge --}}
        @if($post->category)
            <a href="{{ route('pergament.blog.category', \Illuminate\Support\Str::slug($post->category)) }}" class="inline-block text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-0.5 rounded-full mb-3 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                {{ $post->category }}
            </a>
        @endif

        {{-- Title --}}
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            <a href="{{ route('pergament.blog.show', $post->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                {{ $post->title }}
            </a>
        </h2>

        {{-- Excerpt --}}
        @if($post->excerpt)
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">
                {{ $post->excerpt }}
            </p>
        @endif

        {{-- Meta --}}
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <time datetime="{{ $post->date->toDateString() }}">
                {{ $post->date->format('M j, Y') }}
            </time>

            @if(count($post->authors) > 0)
                <span>
                    {{ collect($post->authors)->pluck('name')->implode(', ') }}
                </span>
            @endif
        </div>
    </div>
</article>
