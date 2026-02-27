(function () {
    const copySvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';
    const checkSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

    document.querySelectorAll('main pre').forEach(function (pre) {
        const btn = document.createElement('button');
        btn.className = 'copy-code-btn';
        btn.setAttribute('aria-label', 'Copy code to clipboard');
        btn.innerHTML = copySvg;
        btn.addEventListener('click', function () {
            const code = pre.querySelector('code');
            const text = code ? code.innerText : pre.innerText;
            navigator.clipboard.writeText(text).then(function () {
                btn.innerHTML = checkSvg;
                setTimeout(function () {
                    btn.innerHTML = copySvg;
                }, 2000);
            });
        });
        pre.appendChild(btn);
    });
})();
