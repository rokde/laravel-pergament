(function () {
    const overlay = document.createElement('div');
    overlay.className = 'pergament-lightbox';
    const lightboxImg = document.createElement('img');
    overlay.appendChild(lightboxImg);
    document.body.appendChild(overlay);

    function open(src, alt) {
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';
        overlay.classList.add('is-open');
        document.body.classList.add('lightbox-open');
    }

    function close() {
        overlay.classList.remove('is-open');
        document.body.classList.remove('lightbox-open');
    }

    overlay.addEventListener('click', close);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });

    document.querySelectorAll('main img:not(.rounded-full)').forEach(function (img) {
        img.classList.add('pergament-zoomable');
        img.addEventListener('click', function (e) {
            e.stopPropagation();
            open(img.src, img.alt);
        });
    });
})();
