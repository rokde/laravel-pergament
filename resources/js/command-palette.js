(function () {
    const backdrop = document.getElementById('cmd-palette-backdrop');
    const input = document.getElementById('cmd-palette-input');
    const resultsEl = document.getElementById('cmd-palette-results');
    if (!backdrop || !input || !resultsEl) return;

    const searchUrl = (window.PergamentConfig && window.PergamentConfig.searchUrl) || null;
    if (!searchUrl) return;

    const COMMANDS = [
        { title: 'Toggle dark mode', excerpt: 'Switch between light and dark theme', type: 'cmd', action: 'dark-mode' },
        { title: 'Increase font size', excerpt: 'Make text larger', type: 'cmd', action: 'font-size-increase' },
        { title: 'Decrease font size', excerpt: 'Make text smaller', type: 'cmd', action: 'font-size-decrease' },
        { title: 'Toggle dyslexic font', excerpt: 'Switch to OpenDyslexic font', type: 'cmd', action: 'dyslexic' },
    ];

    let activeIdx = -1;
    let results = [];
    let debounce = null;

    function executeCommand(action) {
        var btnId = action === 'dark-mode' ? 'dark-mode-toggle'
            : action === 'font-size-increase' ? 'font-size-increase'
            : action === 'font-size-decrease' ? 'font-size-decrease'
            : action === 'dyslexic' ? 'dyslexic-toggle'
            : null;

        if (btnId) {
            var btn = document.getElementById(btnId);
            if (btn) btn.click();
        }
    }

    function open() {
        backdrop.classList.add('is-open');
        document.body.classList.add('cmd-open');
        input.value = '';
        results = [];
        activeIdx = -1;
        setTimeout(function () { input.focus(); }, 30);
        loadSuggestions();
    }

    function close() {
        backdrop.classList.remove('is-open');
        document.body.classList.remove('cmd-open');
    }

    function setActive(idx) {
        const items = resultsEl.querySelectorAll('.pergament-cmd-result');
        items.forEach(function (el, i) { el.classList.toggle('is-active', i === idx); });
        activeIdx = idx;
        if (idx >= 0 && items[idx]) {
            items[idx].scrollIntoView({ block: 'nearest' });
        }
    }

    function navigate() {
        if (activeIdx >= 0 && results[activeIdx]) {
            var result = results[activeIdx];
            if (result.type === 'cmd') {
                executeCommand(result.action);
            } else {
                window.location.href = result.url;
            }
            close();
        }
    }

    function typeLabel(type) {
        if (type === 'doc') return 'Doc';
        if (type === 'post') return 'Post';
        if (type === 'page') return 'Page';
        if (type === 'cmd') return 'Cmd';
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    function render(data) {
        results = data;
        activeIdx = -1;
        resultsEl.innerHTML = '';

        if (data.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'pergament-cmd-empty';
            empty.textContent = 'No results found.';
            resultsEl.appendChild(empty);
            return;
        }

        data.forEach(function (result, i) {
            const a = document.createElement('a');
            a.href = result.type === 'cmd' ? '#' : result.url;
            a.className = 'pergament-cmd-result';
            a.setAttribute('role', 'option');

            const badge = document.createElement('span');
            badge.className = 'pergament-cmd-result-badge pergament-cmd-result-badge--' + result.type;
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

            a.addEventListener('mouseenter', function () { setActive(i); });
            a.addEventListener('click', function (e) {
                e.preventDefault();
                if (result.type === 'cmd') {
                    executeCommand(result.action);
                } else {
                    window.location.href = result.url;
                }
                close();
            });

            resultsEl.appendChild(a);
        });
    }

    function loadSuggestions() {
        fetch(searchUrl + '?q=', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (r) { return r.json(); }).then(function (data) {
            render(data.concat(COMMANDS));
        }).catch(function () {
            render(COMMANDS);
        });
    }

    function doSearch(q) {
        if (q.length === 0) {
            loadSuggestions();
            return;
        }
        if (q.length < 2) { resultsEl.innerHTML = ''; results = []; return; }
        fetch(searchUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (r) { return r.json(); }).then(render).catch(function () {});
    }

    input.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () { doSearch(input.value.trim()); }, 200);
    });

    input.addEventListener('keydown', function (e) {
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

    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) close();
    });

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            backdrop.classList.contains('is-open') ? close() : open();
        }
    });

    document.querySelectorAll('input[name="q"]').forEach(function (navInput) {
        navInput.addEventListener('mousedown', function (e) {
            e.preventDefault();
            open();
        });
        navInput.addEventListener('focus', function () {
            navInput.blur();
            open();
        });
    });
})();
