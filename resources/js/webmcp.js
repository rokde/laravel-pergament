(function () {
    const cfg = window.PergamentConfig || {};
    if (!cfg.webMcp || !cfg.searchUrl) return;

    const modelContext = navigator.modelContext;
    if (!modelContext || typeof modelContext.registerTool !== 'function') return;

    const searchBase = new URL(cfg.searchUrl, window.location.href);
    const isStatic = searchBase.pathname.endsWith('.json');
    const LIMIT = 20;
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

    modelContext.registerTool({
        name: 'search',
        description: 'Search this site\'s documentation, blog posts and pages. Returns matching titles, excerpts and URLs.',
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
                return { content: [{ type: 'text', text: 'Please provide a search query.' }], isError: true };
            }

            return fetchResults(query).then(function (results) {
                const items = (Array.isArray(results) ? results : []).slice(0, LIMIT).map(function (it) {
                    return {
                        title: it.title || '',
                        excerpt: it.excerpt || '',
                        url: absolute(it.url),
                        type: it.type || '',
                    };
                });

                return { content: [{ type: 'text', text: JSON.stringify(items) }] };
            }).catch(function () {
                return { content: [{ type: 'text', text: 'Search failed.' }], isError: true };
            });
        },
    });
})();
