@props(['statistics' => []])

@if(count($statistics) > 0)
    <div class="pergament-content-statistics text-xs text-gray-500 dark:text-gray-400 space-y-1.5 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        @isset($statistics['reading_time'])
            <div class="flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $statistics['reading_time'] }} min read</span>
            </div>
        @endisset

        @isset($statistics['word_count'])
            <div class="flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>{{ number_format($statistics['word_count']) }} words</span>
            </div>
        @endisset

        @isset($statistics['character_count'])
            <div class="flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                <span>{{ number_format($statistics['character_count']) }} characters</span>
            </div>
        @endisset

        @isset($statistics['paragraph_count'])
            <div class="flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h12"></path></svg>
                <span>{{ $statistics['paragraph_count'] }} {{ $statistics['paragraph_count'] === 1 ? 'paragraph' : 'paragraphs' }}</span>
            </div>
        @endisset

        @isset($statistics['last_modified'])
            <div class="flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Modified {{ $statistics['last_modified']->format('M j, Y') }}</span>
            </div>
        @endisset

        @isset($statistics['content_age'])
            <div class="flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>{{ $statistics['content_age']->diffForHumans(['parts' => 2, 'short' => true]) }} old</span>
            </div>
        @endisset
    </div>
@endif
