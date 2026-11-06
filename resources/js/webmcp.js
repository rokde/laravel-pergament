(function () {
    const cfg = window.PergamentConfig || {};
    if (!cfg.webMcp || !cfg.searchUrl) return;

    // Current spec: document.modelContext. Older Chrome builds and polyfills: navigator.modelContext.
    const modelContext = document.modelContext || navigator.modelContext;
    const isLegacy = !document.modelContext && !!navigator.modelContext;

    const searchBase = new URL(cfg.searchUrl, window.location.href);
    const isStatic = searchBase.pathname.endsWith('.json');
    const LIMIT = 20;
    const DESCRIPTION = 'Search this site\'s documentation, blog posts and pages. Returns matching titles, excerpts and URLs.';
    let staticIndex = null;

    function absolute(u) {
        try {
            return new URL(u, searchBase).href;
        } catch (e) {
            return u;
        }
    }

    function fetchResults(query) {
        if (isStatic) {
            const load = staticIndex
                ? Promise.resolve(staticIndex)
                : fetch(searchBase).then(function (r) { return r.json(); }).then(function (d) {
                    staticIndex = Array.isArray(d) ? d : [];
                    return staticIndex;
                });

            return load.then(function (index) {
                const t = query.toLowerCase();
                return index.filter(function (it) {
                    return (it.title || '').toLowerCase().includes(t)
                        || (it.excerpt || '').toLowerCase().includes(t)
                        || (it.content || '').toLowerCase().includes(t);
                });
            });
        }

        const url = new URL(searchBase);
        url.searchParams.set('q', query);

        return fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); });
    }

    function search(query) {
        return fetchResults(query).then(function (results) {
            return (Array.isArray(results) ? results : []).slice(0, LIMIT).map(function (it) {
                return {
                    title: it.title || '',
                    excerpt: it.excerpt || '',
                    url: absolute(it.url),
                    type: it.type || '',
                };
            });
        });
    }

    // Declarative WebMCP: the header search form carries toolname/tooldescription.
    // When an agent submits it, answer with JSON results instead of navigating.
    const form = document.querySelector('form[data-pergament-webmcp-form]');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!event.agentInvoked || typeof event.respondWith !== 'function') return;

            event.preventDefault();
            const input = form.querySelector('[name="q"]');
            const query = String((input && input.value) || '').trim();
            event.respondWith(query ? search(query) : Promise.resolve([]));
        });
    }

    if (!modelContext || typeof modelContext.registerTool !== 'function') return;

    // Older implementations expect an MCP-style result; the current spec takes plain data.
    function result(data, isError) {
        if (!isLegacy) {
            return isError ? Promise.reject(new Error(data)) : data;
        }

        const text = typeof data === 'string' ? data : JSON.stringify(data);
        return isError ? { content: [{ type: 'text', text: text }], isError: true } : { content: [{ type: 'text', text: text }] };
    }

    const TOOL = {
        name: 'search',
        title: 'Search',
        description: DESCRIPTION,
        inputSchema: {
            type: 'object',
            properties: {
                query: { type: 'string', description: 'Text to search for' },
            },
            required: ['query'],
        },
        annotations: { readOnlyHint: true },
        execute: function (args) {
            const query = String((args && args.query) || '').trim();
            if (!query) {
                return result('Please provide a search query.', true);
            }

            return search(query)
                .then(function (items) { return result(items, false); })
                .catch(function () { return result('Search failed.', true); });
        },
    };

    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

    function register() {
        try {
            const registered = modelContext.registerTool(TOOL, controller ? { signal: controller.signal } : undefined);
            if (registered && typeof registered.catch === 'function') {
                registered.catch(function () {});
            }
        } catch (e) {
            // A tool named "search" already exists.
        }
    }

    // The declarative form may already provide "search". Only skip our own
    // registration when the browser confirms it, so a hidden or unsupported
    // form never leaves the page without a tool.
    if (typeof modelContext.getTools !== 'function') {
        register();
        return;
    }

    Promise.resolve()
        .then(function () { return modelContext.getTools(); })
        .then(function (tools) {
            const exists = Array.isArray(tools) && tools.some(function (t) { return t && t.name === 'search'; });
            if (!exists) register();
        })
        .catch(register);
})();
