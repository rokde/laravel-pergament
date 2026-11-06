(function () {
    function scrollActiveNavIntoView() {
        const nav = document.getElementById('docs-sidebar-nav');
        const active = nav && nav.querySelector('[data-docs-nav-active]');
        if (!nav || !active || nav.scrollHeight <= nav.clientHeight) return;

        const offset = 12;
        let target = active;
        // Prefer showing the chapter heading when the whole chapter block fits.
        const chapter = active.closest('div');
        const heading = chapter && chapter.querySelector('h3');
        if (heading) {
            const span = active.getBoundingClientRect().bottom - heading.getBoundingClientRect().top;
            if (span <= nav.clientHeight - offset) target = heading;
        }

        const delta = target.getBoundingClientRect().top - nav.getBoundingClientRect().top;
        nav.scrollTop = Math.max(0, nav.scrollTop + delta - offset);
    }

    scrollActiveNavIntoView();

    const toggle = document.getElementById('docs-sidebar-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', function () {
        const sidebar = document.getElementById('docs-sidebar');
        if (!sidebar) return;
        sidebar.classList.toggle('hidden');
        if (!sidebar.classList.contains('hidden')) scrollActiveNavIntoView();
    });
})();
