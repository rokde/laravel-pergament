@extends('pergament::layouts.docs')

@section('seo')
    <x-pergament::seo-head :seo="$seo" />
@endsection

@section('docs-content')
<article>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">
        {{ $page['title'] }}
    </h1>

    @if($page['excerpt'])
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
            {{ $page['excerpt'] }}
        </p>
    @endif

    <div class="prose max-w-none prose-slate dark:prose-invert prose-headings:scroll-mt-20 prose-headings:font-semibold prose-a:text-primary prose-a:no-underline hover:prose-a:underline prose-code:rounded prose-code:bg-muted prose-code:text-sm prose-code:font-normal prose-code:before:content-none prose-code:after:content-none prose-pre:bg-slate-900 prose-pre:dark:bg-slate-950 prose-img:rounded-lg">
        {!! $page['htmlContent'] !!}
    </div>

    {{-- Previous / Next navigation --}}
    <nav class="mt-12 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6 print:hidden">
        @if($page['previousPage'])
            <a href="{{ $page['previousPage']['url'] }}" class="group flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg class="size-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                {{ $page['previousPage']['title'] }}
            </a>
        @else
            <span></span>
        @endif

        @if($page['nextPage'])
            <a href="{{ $page['nextPage']['url'] }}" class="group flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                {{ $page['nextPage']['title'] }}
                <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        @endif
    </nav>
</article>

@push('scripts')
<script>
    document.querySelectorAll('.prose h2[id], .prose h3[id], .prose h4[id]').forEach(function(heading) {
        const btn = document.createElement('button');
        btn.className = 'heading-anchor';
        btn.setAttribute('aria-label', 'Copy link to section');
        btn.textContent = '¶';
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const url = window.location.origin + window.location.pathname + '#' + heading.id;
            navigator.clipboard.writeText(url).then(function() {
                btn.textContent = 'Copied';
                setTimeout(function() { btn.textContent = '¶'; }, 1500);
            });
        });
        heading.appendChild(btn);
    });
</script>
@endpush
@endsection
