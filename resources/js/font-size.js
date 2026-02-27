(function () {
    const FONT_STEPS = [75, 87.5, 100, 112.5, 125, 137.5, 150];
    const DEFAULT_IDX = 2; // 100%

    const btnDec = document.getElementById('font-size-decrease');
    const btnInc = document.getElementById('font-size-increase');
    const btnDecMobile = document.getElementById('font-size-decrease-mobile');
    const btnIncMobile = document.getElementById('font-size-increase-mobile');

    if (!btnDec || !btnInc) return;

    function getCurrentFontIdx() {
        const val = localStorage.getItem('pergament-font-size');
        if (!val) return DEFAULT_IDX;
        const pct = parseFloat(val);
        const idx = FONT_STEPS.indexOf(pct);
        return idx >= 0 ? idx : DEFAULT_IDX;
    }

    function updateButtonStates(idx) {
        const atMin = idx === 0;
        const atMax = idx === FONT_STEPS.length - 1;
        const decTitle = atMin
            ? 'You are on the smallest font size'
            : 'Decrease font size to ' + FONT_STEPS[idx - 1] + '%';
        const incTitle = atMax
            ? 'You are on the biggest font size'
            : 'Increase font size to ' + FONT_STEPS[idx + 1] + '%';

        btnDec.disabled = atMin;
        btnDec.title = decTitle;
        btnDec.classList.toggle('opacity-30', atMin);
        btnDec.classList.toggle('cursor-not-allowed', atMin);
        btnInc.disabled = atMax;
        btnInc.title = incTitle;
        btnInc.classList.toggle('opacity-30', atMax);
        btnInc.classList.toggle('cursor-not-allowed', atMax);
        if (btnDecMobile) {
            btnDecMobile.disabled = atMin;
            btnDecMobile.title = decTitle;
            btnDecMobile.classList.toggle('opacity-30', atMin);
            btnDecMobile.classList.toggle('cursor-not-allowed', atMin);
        }
        if (btnIncMobile) {
            btnIncMobile.disabled = atMax;
            btnIncMobile.title = incTitle;
            btnIncMobile.classList.toggle('opacity-30', atMax);
            btnIncMobile.classList.toggle('cursor-not-allowed', atMax);
        }
    }

    function applyFontSize(idx) {
        const pct = FONT_STEPS[idx];
        localStorage.setItem('pergament-font-size', pct);
        let style = document.getElementById('pergament-font-size-style');
        if (!style) {
            style = document.createElement('style');
            style.id = 'pergament-font-size-style';
            document.head.appendChild(style);
        }
        style.textContent = '.prose { font-size: ' + pct + '%; }';
        updateButtonStates(idx);
    }

    function decreaseFontSize() {
        const idx = getCurrentFontIdx();
        if (idx > 0) applyFontSize(idx - 1);
    }

    function increaseFontSize() {
        const idx = getCurrentFontIdx();
        if (idx < FONT_STEPS.length - 1) applyFontSize(idx + 1);
    }

    btnDec.addEventListener('click', decreaseFontSize);
    btnInc.addEventListener('click', increaseFontSize);
    if (btnDecMobile) btnDecMobile.addEventListener('click', decreaseFontSize);
    if (btnIncMobile) btnIncMobile.addEventListener('click', increaseFontSize);

    updateButtonStates(getCurrentFontIdx());
})();
