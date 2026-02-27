(function () {
    if (!('serviceWorker' in navigator)) return;
    const swUrl = window.PergamentConfig && window.PergamentConfig.swUrl;
    if (!swUrl) return;
    navigator.serviceWorker.register(swUrl);
})();
