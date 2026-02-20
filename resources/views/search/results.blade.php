@extends('pergament::layouts.app')

@section('seo')
    <x-pergament::seo-head :seo="$seo" />
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
        Search
    </h1>

    {{-- Search form --}}
    <form action="{{ route('pergament.search') }}" method="GET" class="mb-8">
        <div class="relative">
            <input
                type="text"
                name="q"
                value="{{ $query }}"
                placeholder="Search documentation and blog..."
                class="w-full pl-4 pr-12 py-3 text-base rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 pergament-input"
                autofocus
            >
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </div>
    </form>

    {{-- Results --}}
    @if($query !== '')
        @if($results->isEmpty())
            <div class="text-center py-12">
                <svg class="size-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    No results found for "{{ $query }}"
                </p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">
                    Try a different search term or browse the documentation.
                </p>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                {{ $results->count() }} {{ Str::plural('result', $results->count()) }} for "{{ $query }}"
            </p>

            <div class="space-y-6">
                @foreach($results as $result)
                    <a href="{{ $result['url'] }}" class="block group">
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full {{ $result['type'] === 'doc' ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400' }}">
                                    {{ $result['type'] === 'doc' ? 'Documentation' : 'Blog' }}
                                </span>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white pergament-primary-group-hover transition-colors">
                                {{ $result['title'] }}
                            </h3>
                            @if(!empty($result['excerpt']))
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ $result['excerpt'] }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection
