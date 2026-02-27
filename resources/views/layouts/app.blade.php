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

            // Font size
            const FONT_STEPS = [75, 87.5, 100, 112.5, 125, 137.5, 150];
            const DEFAULT_IDX = 2; // 100%

            const btnDec = document.getElementById('font-size-decrease');
            const btnInc = document.getElementById('font-size-increase');
            const btnDecMobile = document.getElementById('font-size-decrease-mobile');
            const btnIncMobile = document.getElementById('font-size-increase-mobile');

            function getCurrentFontIdx() {
                const stored = localStorage.getItem('pergament-font-size');
                if (!stored) return DEFAULT_IDX;
                const pct = parseFloat(stored);
                const idx = FONT_STEPS.indexOf(pct);
                return idx >= 0 ? idx : DEFAULT_IDX;
            }

            function updateButtonStates(idx) {
                const atMin = idx === 0;
                const atMax = idx === FONT_STEPS.length - 1;
                const decTitle = atMin
                    ? 'You are on the smallest font size'
                    : 'Decrease font size to ' + FONT_STEPS[idx - 1] + '%';
                const incTitle = atMax
                    ? 'You are on the biggest font size'
                    : 'Increase font size to ' + FONT_STEPS[idx + 1] + '%';

                btnDec.disabled = atMin;
                btnDec.title = decTitle;
                btnDec.classList.toggle('opacity-30', atMin);
                btnDec.classList.toggle('cursor-not-allowed', atMin);
                btnInc.disabled = atMax;
                btnInc.title = incTitle;
                btnInc.classList.toggle('opacity-30', atMax);
                btnInc.classList.toggle('cursor-not-allowed', atMax);
                if (btnDecMobile) {
                    btnDecMobile.disabled = atMin;
                    btnDecMobile.title = decTitle;
                    btnDecMobile.classList.toggle('opacity-30', atMin);
                    btnDecMobile.classList.toggle('cursor-not-allowed', atMin);
                }
                if (btnIncMobile) {
                    btnIncMobile.disabled = atMax;
                    btnIncMobile.title = incTitle;
                    btnIncMobile.classList.toggle('opacity-30', atMax);
                    btnIncMobile.classList.toggle('cursor-not-allowed', atMax);
                }
            }

            function applyFontSize(idx) {
                const pct = FONT_STEPS[idx];
                localStorage.setItem('pergament-font-size', pct);
                let style = document.getElementById('pergament-font-size-style');
                if (!style) {
                    style = document.createElement('style');
                    style.id = 'pergament-font-size-style';
                    document.head.appendChild(style);
                }
                style.textContent = '.prose { font-size: ' + pct + '%; }';
                updateButtonStates(idx);
            }

            function decreaseFontSize() {
                const idx = getCurrentFontIdx();
                if (idx > 0) applyFontSize(idx - 1);
            }

            function increaseFontSize() {
                const idx = getCurrentFontIdx();
                if (idx < FONT_STEPS.length - 1) applyFontSize(idx + 1);
            }

            btnDec.addEventListener('click', decreaseFontSize);
            btnInc.addEventListener('click', increaseFontSize);
            if (btnDecMobile) btnDecMobile.addEventListener('click', decreaseFontSize);
            if (btnIncMobile) btnIncMobile.addEventListener('click', increaseFontSize);

            updateButtonStates(getCurrentFontIdx());

            // Dyslexic font toggle
            const btnDyslexic = document.getElementById('dyslexic-toggle');
            const btnDyslexicMobile = document.getElementById('dyslexic-toggle-mobile');

            function updateDyslexicButton(enabled) {
                const title = enabled ? 'Switch to normal font' : 'Switch to OpenDyslexic font';
                btnDyslexic.title = title;
                btnDyslexic.classList.toggle('pergament-active-nav', enabled);
                if (btnDyslexicMobile) {
                    btnDyslexicMobile.title = title;
                    btnDyslexicMobile.classList.toggle('pergament-active-nav', enabled);
                }
            }

            function toggleDyslexic() {
                const enabled = document.documentElement.classList.toggle('dyslexic');
                localStorage.setItem('pergament-dyslexic', enabled ? '1' : '0');
                updateDyslexicButton(enabled);
            }

            btnDyslexic.addEventListener('click', toggleDyslexic);
            if (btnDyslexicMobile) btnDyslexicMobile.addEventListener('click', toggleDyslexic);

            updateDyslexicButton(document.documentElement.classList.contains('dyslexic'));
        })();
    </script>

    @stack('scripts')

    <script>
        (function() {
            const overlay = document.createElement('div');
            overlay.className = 'pergament-lightbox';
            const lightboxImg = document.createElement('img');
            overlay.appendChild(lightboxImg);
            document.body.appendChild(overlay);

            function open(src, alt) {
                lightboxImg.src = src;
                lightboxImg.alt = alt || '';
                overlay.classList.add('is-open');
                document.body.classList.add('lightbox-open');
            }

            function close() {
                overlay.classList.remove('is-open');
                document.body.classList.remove('lightbox-open');
            }

            overlay.addEventListener('click', close);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') close();
            });

            // Attach to all content images; skip rounded-full avatars/icons
            document.querySelectorAll('main img:not(.rounded-full)').forEach(function(img) {
                img.classList.add('pergament-zoomable');
                img.addEventListener('click', function(e) {
                    e.stopPropagation();
                    open(img.src, img.alt);
                });
            });
        })();
    </script>

    @if(config('pergament.search.enabled'))
    <script>
        (function() {
            const backdrop = document.getElementById('cmd-palette-backdrop');
            const input    = document.getElementById('cmd-palette-input');
            const resultsEl = document.getElementById('cmd-palette-results');
            if (!backdrop || !input || !resultsEl) return;

            const searchUrl = '{{ route('pergament.search') }}';
            let activeIdx = -1;
            let results   = [];
            let debounce  = null;

            function open() {
                backdrop.classList.add('is-open');
                document.body.classList.add('cmd-open');
                input.value = '';
                resultsEl.innerHTML = '';
                results = [];
                activeIdx = -1;
                setTimeout(function() { input.focus(); }, 30);
            }

            function close() {
                backdrop.classList.remove('is-open');
                document.body.classList.remove('cmd-open');
            }

            function setActive(idx) {
                const items = resultsEl.querySelectorAll('.pergament-cmd-result');
                items.forEach(function(el, i) { el.classList.toggle('is-active', i === idx); });
                activeIdx = idx;
                if (idx >= 0 && items[idx]) {
                    items[idx].scrollIntoView({ block: 'nearest' });
                }
            }

            function navigate() {
                if (activeIdx >= 0 && results[activeIdx]) {
                    window.location.href = results[activeIdx].url;
                    close();
                }
            }

            function typeLabel(type) {
                if (type === 'doc')  return 'Doc';
                if (type === 'post') return 'Post';
                if (type === 'page') return 'Page';
                return type.charAt(0).toUpperCase() + type.slice(1);
            }

            function render(data) {
                results   = data;
                activeIdx = -1;
                resultsEl.innerHTML = '';

                if (data.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'pergament-cmd-empty';
                    empty.textContent = 'No results found.';
                    resultsEl.appendChild(empty);
                    return;
                }

                data.forEach(function(result, i) {
                    const a = document.createElement('a');
                    a.href = result.url;
                    a.className = 'pergament-cmd-result';
                    a.setAttribute('role', 'option');

                    const badge = document.createElement('span');
                    badge.className = 'pergament-cmd-result-badge';
                    badge.textContent = typeLabel(result.type);

                    const body = document.createElement('div');
                    body.className = 'pergament-cmd-result-body';

                    const title = document.createElement('div');
                    title.className = 'pergament-cmd-result-title';
                    title.textContent = result.title;
                    body.appendChild(title);

                    if (result.excerpt) {
                        const excerpt = document.createElement('div');
                        excerpt.className = 'pergament-cmd-result-excerpt';
                        excerpt.textContent = result.excerpt;
                        body.appendChild(excerpt);
                    }

                    a.appendChild(badge);
                    a.appendChild(body);

                    a.addEventListener('mouseenter', function() { setActive(i); });
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.location.href = result.url;
                        close();
                    });

                    resultsEl.appendChild(a);
                });
            }

            function doSearch(q) {
                if (q.length < 2) { resultsEl.innerHTML = ''; results = []; return; }
                fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                }).then(function(r) { return r.json(); }).then(render).catch(function() {});
            }

            input.addEventListener('input', function() {
                clearTimeout(debounce);
                debounce = setTimeout(function() { doSearch(input.value.trim()); }, 200);
            });

            input.addEventListener('keydown', function(e) {
                const items = resultsEl.querySelectorAll('.pergament-cmd-result');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    setActive(Math.min(activeIdx + 1, items.length - 1));
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    setActive(Math.max(activeIdx - 1, 0));
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    navigate();
                } else if (e.key === 'Escape') {
                    close();
                }
            });

            backdrop.addEventListener('click', function(e) {
                if (e.target === backdrop) close();
            });

            document.addEventListener('keydown', function(e) {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    backdrop.classList.contains('is-open') ? close() : open();
                }
            });

            // Nav search inputs open the palette instead of receiving focus
            document.querySelectorAll('input[name="q"]').forEach(function(navInput) {
                navInput.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    open();
                });
                navInput.addEventListener('focus', function() {
                    navInput.blur();
                    open();
                });
            });
        })();
    </script>
    @endif

    <script>
        (function() {
            const copySvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';
            const checkSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

            document.querySelectorAll('main pre').forEach(function(pre) {
                const btn = document.createElement('button');
                btn.className = 'copy-code-btn';
                btn.setAttribute('aria-label', 'Copy code to clipboard');
                btn.innerHTML = copySvg;
                btn.addEventListener('click', function() {
                    const code = pre.querySelector('code');
                    const text = code ? code.innerText : pre.innerText;
                    navigator.clipboard.writeText(text).then(function() {
                        btn.innerHTML = checkSvg;
                        setTimeout(function() {
                            btn.innerHTML = copySvg;
                        }, 2000);
                    });
                });
                pre.appendChild(btn);
            });
        })();
    </script>
</body>
</html>
