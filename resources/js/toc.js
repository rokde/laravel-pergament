(function () {
    const tocLinks = Array.from(document.querySelectorAll('[data-toc-link]'));
    if (!tocLinks.length) return;

    const headings = Array.from(document.querySelectorAll('.prose h2[id], .prose h3[id], .prose h4[id]'));
    if (!headings.length) return;

    const visibleIds = new Set();

    function activate(id) {
        tocLinks.forEach(function (link) {
            const isActive = link.dataset.tocLink === id;
            link.classList.toggle('toc-link-active', isActive);
            if (isActive) {
                link.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function update() {
        for (let i = 0; i < headings.length; i++) {
            if (visibleIds.has(headings[i].id)) {
                activate(headings[i].id);
                return;
            }
        }
        var cutoff = window.scrollY + 100;
        var candidate = null;
        headings.forEach(function (h) {
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
        rootMargin: '-80px 0px -40% 0px',
        threshold: 0,
    });

    headings.forEach(function (h) { observer.observe(h); });
})();
