(function () {
    const btn = document.getElementById('dyslexic-toggle');
    const btnMobile = document.getElementById('dyslexic-toggle-mobile');

    if (!btn) return;

    function updateButton(enabled) {
        const title = enabled ? 'Switch to normal font' : 'Switch to OpenDyslexic font';
        btn.title = title;
        btn.classList.toggle('pergament-active-nav', enabled);
        if (btnMobile) {
            btnMobile.title = title;
            btnMobile.classList.toggle('pergament-active-nav', enabled);
        }
    }

    function toggleDyslexic() {
        const enabled = document.documentElement.classList.toggle('dyslexic');
        localStorage.setItem('pergament-dyslexic', enabled ? '1' : '0');
        updateButton(enabled);
    }

    btn.addEventListener('click', toggleDyslexic);
    if (btnMobile) btnMobile.addEventListener('click', toggleDyslexic);

    updateButton(document.documentElement.classList.contains('dyslexic'));
})();
