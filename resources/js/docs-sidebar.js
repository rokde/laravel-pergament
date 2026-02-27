(function () {
    const toggle = document.getElementById('docs-sidebar-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', function () {
        const sidebar = document.getElementById('docs-sidebar');
        if (sidebar) sidebar.classList.toggle('hidden');
    });
})();
