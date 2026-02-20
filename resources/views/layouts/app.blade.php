<!DOCTYPE html>
<html lang="{{ config('pergament.site.locale', 'en') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @yield('seo')

    @if(config('pergament.favicon'))
        <link rel="icon" href="{{ config('pergament.favicon') }}">
    @endif

    @if(config('pergament.blog.enabled') && config('pergament.blog.feed.enabled'))
        <link rel="alternate" type="application/atom+xml" title="{{ config('pergament.blog.feed.title', config('pergament.site.name', 'Blog') . ' Feed') }}" href="{{ route('pergament.blog.feed') }}">
    @endif

    @if(config('pergament.pwa.enabled'))
        <link rel="manifest" href="/manifest.json">
    @endif

    <link rel="stylesheet" href="{{ asset('vendor/pergament/pergament.css') }}">

    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">

    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Site name --}}
                <a href="/" class="text-lg font-bold text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    {{ config('pergament.site.name', 'Laravel Pergament') }}
                </a>

                {{-- Desktop nav --}}
                <div class="hidden md:flex items-center gap-6">
                    @if(config('pergament.docs.enabled'))
                        <a href="{{ route('pergament.docs.index') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            {{ config('pergament.docs.title', 'Documentation') }}
                        </a>
                    @endif

                    @if(config('pergament.blog.enabled'))
                        <a href="{{ route('pergament.blog.index') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            {{ config('pergament.blog.title', 'Blog') }}
                        </a>
                    @endif

                    @if(config('pergament.search.enabled'))
                        <form action="{{ route('pergament.search') }}" method="GET" class="relative">
                            <input
                                type="text"
                                name="q"
                                placeholder="Search..."
                                class="w-44 pl-3 pr-8 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </button>
                        </form>
                    @endif

                    {{-- Dark mode toggle --}}
                    <button
                        id="dark-mode-toggle"
                        type="button"
                        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        aria-label="Toggle dark mode"
                    >
                        <svg class="size-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="size-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>

                {{-- Mobile menu button --}}
                <button
                    id="mobile-menu-toggle"
                    type="button"
                    class="md:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                    aria-label="Toggle menu"
                >
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 space-y-2">
                @if(config('pergament.docs.enabled'))
                    <a href="{{ route('pergament.docs.index') }}" class="block text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white py-1">
                        {{ config('pergament.docs.title', 'Documentation') }}
                    </a>
                @endif

                @if(config('pergament.blog.enabled'))
                    <a href="{{ route('pergament.blog.index') }}" class="block text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white py-1">
                        {{ config('pergament.blog.title', 'Blog') }}
                    </a>
                @endif

                @if(config('pergament.search.enabled'))
                    <form action="{{ route('pergament.search') }}" method="GET" class="pt-1">
                        <input
                            type="text"
                            name="q"
                            placeholder="Search..."
                            class="w-full pl-3 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </form>
                @endif

                <button
                    id="dark-mode-toggle-mobile"
                    type="button"
                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 py-1"
                >
                    <svg class="size-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg class="size-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    Toggle dark mode
                </button>
            </div>
        </div>
    </nav>

    {{-- Main content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} {{ config('pergament.site.name', config('app.name', 'Pergament')) }}. All rights reserved.
            </p>
        </div>
    </footer>

    @if(config('pergament.pwa.enabled'))
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('{{ route('pergament.sw') }}');
            }
        </script>
    @endif

    <script>
        (function() {
            // Dark mode
            function setDarkMode(enabled) {
                document.documentElement.classList.toggle('dark', enabled);
                localStorage.setItem('pergament-dark-mode', enabled ? '1' : '0');
            }

            const stored = localStorage.getItem('pergament-dark-mode');
            if (stored === '1' || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                setDarkMode(true);
            }

            document.getElementById('dark-mode-toggle').addEventListener('click', function() {
                setDarkMode(!document.documentElement.classList.contains('dark'));
            });

            const mobileToggle = document.getElementById('dark-mode-toggle-mobile');
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    setDarkMode(!document.documentElement.classList.contains('dark'));
                });
            }

            // Mobile menu
            document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.toggle('hidden');
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
