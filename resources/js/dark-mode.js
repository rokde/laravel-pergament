function setDarkMode(enabled) {
    document.documentElement.classList.toggle('dark', enabled);
    localStorage.setItem('pergament-dark-mode', enabled ? '1' : '0');
}

const stored = localStorage.getItem('pergament-dark-mode');
if (stored === '1' || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    setDarkMode(true);
}

const toggle = document.getElementById('dark-mode-toggle');
if (toggle) {
    toggle.addEventListener('click', function () {
        setDarkMode(!document.documentElement.classList.contains('dark'));
    });
}

const mobileToggle = document.getElementById('dark-mode-toggle-mobile');
if (mobileToggle) {
    mobileToggle.addEventListener('click', function () {
        setDarkMode(!document.documentElement.classList.contains('dark'));
    });
}
