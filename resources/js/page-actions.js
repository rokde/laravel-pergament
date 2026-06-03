(function () {
    document.querySelectorAll('[data-pergament-copy-page]').forEach(function (btn) {
        const label = btn.querySelector('[data-copy-label]');
        const initialLabel = label ? label.textContent : '';
        const markdownUrl = btn.getAttribute('data-markdown-url');

        if (!markdownUrl || !navigator.clipboard) {
            return;
        }

        btn.addEventListener('click', function () {
            fetch(markdownUrl)
                .then(function (response) {
                    return response.text();
                })
                .then(function (text) {
                    return navigator.clipboard.writeText(text);
                })
                .then(function () {
                    if (!label) {
                        return;
                    }

                    label.textContent = 'Copied';

                    setTimeout(function () {
                        label.textContent = initialLabel;
                    }, 2000);
                });
        });
    });
})();
