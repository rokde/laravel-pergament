(function () {
    const pilcrowSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M13 4v16M17 4v16M19 4H9.5a4.5 4.5 0 0 0 0 9H13"/></svg>';
    const checkSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

    document.querySelectorAll('.prose h2[id], .prose h3[id], .prose h4[id]').forEach(function (heading) {
        const btn = document.createElement('button');
        btn.className = 'heading-anchor';
        btn.setAttribute('aria-label', 'Copy link to section');
        btn.innerHTML = pilcrowSvg;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const url = window.location.origin + window.location.pathname + '#' + heading.id;
            navigator.clipboard.writeText(url).then(function () {
                btn.innerHTML = checkSvg;
                setTimeout(function () { btn.innerHTML = pilcrowSvg; }, 2000);
            });
        });
        heading.appendChild(btn);
    });
})();
