let voices = [];

function loadVoices() {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        voices = window.speechSynthesis.getVoices() || [];
    }
}

if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    loadVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }
}

/**
 * Tìm voice phù hợp nhất cho ngôn ngữ/accent được chỉ định ('en-US' hoặc 'en-GB')
 */
function findVoice(lang) {
    if (!voices || voices.length === 0) {
        loadVoices();
    }

    if (!voices || voices.length === 0) return null;

    const target = (lang || 'en-US').toLowerCase().replace('_', '-');
    const isUk = target.includes('gb') || target.includes('uk');
    const isUs = target.includes('us');

    // 1. Khớp chính xác mã ngôn ngữ lang (vd: en-US, en-GB)
    let matchedVoice = voices.find(v => v.lang.toLowerCase().replace('_', '-') === target);
    if (matchedVoice) return matchedVoice;

    // 2. Khớp theo tên và accent đặc trưng theo hệ điều hành/trình duyệt
    if (isUk) {
        matchedVoice = voices.find(v => 
            v.lang.toLowerCase().includes('gb') || 
            /uk|british|united kingdom|great britain|daniel|george|serena|oliver|stephanie|hazel|arthur/i.test(v.name)
        );
        if (matchedVoice) return matchedVoice;
    } else if (isUs) {
        matchedVoice = voices.find(v => 
            v.lang.toLowerCase().includes('us') || 
            /us|united states|samantha|alex|david|mark|zira|karen|fred|victoria/i.test(v.name)
        );
        if (matchedVoice) return matchedVoice;
    }

    // 3. Khớp bất kỳ voice tiếng Anh nào có chứa locale tương ứng
    matchedVoice = voices.find(v => v.lang.toLowerCase().includes(isUk ? 'gb' : 'us'));
    if (matchedVoice) return matchedVoice;

    // 4. Fallback: giọng tiếng Anh bất kỳ
    return voices.find(v => v.lang.toLowerCase().startsWith('en')) || null;
}

export function speakText(text, lang = 'en-US', rate = 0.9) {
    if (!('speechSynthesis' in window)) {
        console.warn('Trình duyệt không hỗ trợ Web Speech API.');
        return false;
    }

    if (!text || typeof text !== 'string') {
        return false;
    }

    window.speechSynthesis.cancel();

    // Chuẩn hóa mã ngôn ngữ
    const standardLang = (lang === 'uk' || lang === 'en-UK' || lang === 'en-GB') ? 'en-GB' : 'en-US';

    const utterance = new SpeechSynthesisUtterance(text.trim());
    utterance.lang = standardLang;
    utterance.rate = rate;

    const matchedVoice = findVoice(standardLang);
    if (matchedVoice) {
        utterance.voice = matchedVoice;
    }

    window.speechSynthesis.speak(utterance);
    return true;
}

