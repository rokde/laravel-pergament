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

    <style>
        :root {
            --p-primary:         {{ config('pergament.colors.primary', '#3b82f6') }};
            --p-bg:              {{ config('pergament.colors.background', '#ffffff') }};
            /* Derived tints — re-resolved automatically when .dark overrides --p-bg */
            --p-primary-subtle:  color-mix(in oklch, var(--p-primary) 12%, var(--p-bg));
            --p-primary-fg:      color-mix(in oklch, var(--p-primary) 75%, black);
            --p-bg-elevated:     var(--p-bg);
        }
        .dark {
            --p-bg:          #111827;
            --p-bg-elevated: #1f2937;
            --p-primary-fg:  color-mix(in oklch, var(--p-primary) 65%, white);
        }
    </style>

    @stack('styles')

    <script>
        (function() {
            const size = localStorage.getItem('pergament-font-size');
            if (size) {
                const s = document.createElement('style');
                s.id = 'pergament-font-size-style';
                s.textContent = '.prose { font-size: ' + size + '%; }';
                document.head.appendChild(s);
            }
            if (localStorage.getItem('pergament-dyslexic') === '1') {
                document.documentElement.classList.add('dyslexic');
            }
        })();
    </script>
</head>
<body class="min-h-screen flex flex-col pergament-bg text-gray-900 dark:text-gray-100 antialiased">

    <x-pergament::header />

    {{-- Main content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    <x-pergament::footer />

    <script>
        window.PergamentConfig = {
            searchUrl: @json(config('pergament.search.enabled') ? route('pergament.search') : null),
            swUrl: @json(config('pergament.pwa.enabled') ? route('pergament.sw') : null),
        };
    </script>

    @stack('scripts')

    <script src="{{ asset('vendor/pergament/pergament.js') }}"></script>
</body>
</html>
