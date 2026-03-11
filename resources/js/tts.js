const btn = document.getElementById('pergament-tts-btn');

if (btn && 'speechSynthesis' in window) {
    const iconPlay = document.getElementById('pergament-tts-icon-play');
    const iconPause = document.getElementById('pergament-tts-icon-pause');
    const label = document.getElementById('pergament-tts-label');
    const synth = window.speechSynthesis;

    function showPlaying() {
        iconPlay.classList.add('hidden');
        iconPause.classList.remove('hidden');
        label.textContent = 'Pause';
        btn.setAttribute('aria-label', 'Pause reading');
    }

    function showStopped() {
        iconPause.classList.add('hidden');
        iconPlay.classList.remove('hidden');
        label.textContent = 'Listen';
        btn.setAttribute('aria-label', 'Read content aloud');
    }

    function getTextContent() {
        const target = btn.getAttribute('data-tts-target') || '.prose';
        const el = document.querySelector(target);
        if (!el) return '';
        return el.innerText || el.textContent || '';
    }

    function pickVoice(preferredName) {
        const voices = synth.getVoices();
        if (preferredName) {
            const match = voices.find(function (v) {
                return v.name === preferredName;
            });
            if (match) return match;
        }
        return null;
    }

    btn.addEventListener('click', function () {
        if (synth.speaking && !synth.paused) {
            synth.pause();
            showStopped();
            return;
        }

        if (synth.paused) {
            synth.resume();
            showPlaying();
            return;
        }

        const text = getTextContent();
        if (!text) return;

        const utterance = new SpeechSynthesisUtterance(text);

        const config = window.PergamentConfig || {};
        if (config.ttsVoice) {
            const voice = pickVoice(config.ttsVoice);
            if (voice) utterance.voice = voice;
        }
        utterance.rate = config.ttsRate || 1.0;

        utterance.onend = function () {
            showStopped();
        };

        utterance.onerror = function () {
            showStopped();
        };

        synth.cancel();
        synth.speak(utterance);
        showPlaying();
    });

    window.addEventListener('pagehide', function () {
        if (synth.speaking || synth.paused) {
            synth.cancel();
        }
    });
}
