export function speakText(text, lang = 'en-US', rate = 0.9) {
    if (!('speechSynthesis' in window)) {
        console.warn('Trình duyệt không hỗ trợ Web Speech API.');
        return false;
    }

    if (!text || typeof text !== 'string') {
        return false;
    }

    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text.trim());
    utterance.lang = lang;
    utterance.rate = rate;

    window.speechSynthesis.speak(utterance);
    return true;
}
