@props(['post'])
@php /** @var \Pergament\Data\BlogPost $post */ @endphp

<article class="group pergament-card-bg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
    <div class="p-6">
        {{-- Category badge --}}
        @if($post->category)
            <a href="{{ route('pergament.blog.category', \Illuminate\Support\Str::slug($post->category)) }}" class="inline-block text-xs font-medium pergament-primary-badge px-2.5 py-0.5 rounded-full mb-3 transition-colors">
                {{ $post->category }}
            </a>
        @endif

        {{-- Title --}}
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            <a href="{{ route('pergament.blog.show', $post->slug) }}" class="pergament-primary-hover transition-colors">
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
