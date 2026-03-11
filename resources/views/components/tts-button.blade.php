@props(['target' => '.prose'])

<button
    id="pergament-tts-btn"
    data-tts-target="{{ $target }}"
    type="button"
    aria-label="Read content aloud"
    title="Read aloud"
    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer print:hidden"
>
    {{-- Play icon (shown by default) --}}
    <svg id="pergament-tts-icon-play" class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
    </svg>

    {{-- Pause icon (hidden by default) --}}
    <svg id="pergament-tts-icon-pause" class="size-5 hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
    </svg>

    <span id="pergament-tts-label">Listen</span>
</button>
