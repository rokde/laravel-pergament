@extends('pergament::layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:grid lg:grid-cols-12 lg:gap-8">

        {{-- Mobile sidebar toggle --}}
        <button
            id="docs-sidebar-toggle"
            type="button"
            class="lg:hidden mb-4 flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
        >
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            Menu
        </button>

        {{-- Sidebar --}}
        <aside id="docs-sidebar" class="hidden lg:block lg:col-span-3">
            <nav class="sticky top-24 overflow-y-auto max-h-[calc(100vh-8rem)] pb-8 pr-4">
                @isset($navigation)
                    @foreach($navigation as $chapter)
                        <div class="mb-6">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                {{ $chapter['title'] }}
                            </h3>
                            <ul class="space-y-1">
                                @foreach($chapter['pages'] as $navPage)
                                    @php
                                        $isActive = isset($currentChapter, $currentPage) && $currentChapter === $chapter['slug'] && $currentPage === $navPage['slug'];
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ route('pergament.docs.show', ['chapter' => $chapter['slug'], 'page' => $navPage['slug']]) }}"
                                            class="block px-3 py-1.5 text-sm rounded-md transition-colors {{ $isActive ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                                        >
                                            {{ $navPage['title'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endisset
            </nav>
        </aside>

        {{-- Main content --}}
        <div class="lg:col-span-6">
            @yield('docs-content')
        </div>

        {{-- Table of contents --}}
        @if(isset($page['headings']) && count($page['headings']) > 0)
            <aside class="hidden xl:block lg:col-span-3">
                <div id="toc-container" class="sticky top-24 overflow-y-auto max-h-[calc(100vh-8rem)] pb-8 pl-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                        On this page
                    </h4>
                    <ul class="space-y-1.5 border-l border-gray-200 dark:border-gray-700">
                        @foreach($page['headings'] as $heading)
                            <li>
                                <a
                                    href="#{{ $heading->slug }}"
                                    data-toc-link="{{ $heading->slug }}"
                                    class="block text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors {{ $heading->level === 2 ? 'pl-4' : 'pl-' . (($heading->level - 1) * 4) }}"
                                >
                                    {{ $heading->text }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('docs-sidebar-toggle').addEventListener('click', function() {
        const sidebar = document.getElementById('docs-sidebar');
        sidebar.classList.toggle('hidden');
    });

    (function() {
        const tocLinks = Array.from(document.querySelectorAll('[data-toc-link]'));
        if (!tocLinks.length) return;

        const headings = Array.from(document.querySelectorAll('.prose h2[id], .prose h3[id], .prose h4[id]'));
        if (!headings.length) return;

        const visibleIds = new Set();

        function activate(id) {
            tocLinks.forEach(function(link) {
                const isActive = link.dataset.tocLink === id;
                link.classList.toggle('toc-link-active', isActive);
                if (isActive) {
                    link.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        function update() {
            // Prefer the topmost heading that is currently intersecting
            for (let i = 0; i < headings.length; i++) {
                if (visibleIds.has(headings[i].id)) {
                    activate(headings[i].id);
                    return;
                }
            }
            // Nothing intersecting — highlight the last heading scrolled past
            var cutoff = window.scrollY + 100;
            var candidate = null;
            headings.forEach(function(h) {
                if (h.offsetTop <= cutoff) candidate = h.id;
            });
            if (candidate) activate(candidate);
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    visibleIds.add(entry.target.id);
                } else {
                    visibleIds.delete(entry.target.id);
                }
            });
            update();
        }, {
            // Top offset matches scroll-mt-20 (5rem / 80px) on headings; bottom
            // threshold keeps only the upper portion of the page "active".
            rootMargin: '-80px 0px -40% 0px',
            threshold: 0,
        });

        headings.forEach(function(h) { observer.observe(h); });
    })();
</script>
@endpush
@endsection
