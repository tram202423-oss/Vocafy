import { speakText } from '../utils/speech';

/**
 * Alpine.js Component: vocabularyPlayer
 * Quản lý trạng thái và thao tác phát âm từ vựng & câu ví dụ
 */
export default function vocabularyPlayer() {
    return {
        speakingWord: null,
        speakingLang: null,

        speak(text, lang = 'en-US') {
            const standardLang = (lang === 'uk' || lang === 'en-UK' || lang === 'en-GB') ? 'en-GB' : 'en-US';
            this.speakingWord = text;
            this.speakingLang = standardLang;
            const success = speakText(text, standardLang);

            if (!success && !('speechSynthesis' in window)) {
                alert('Trình duyệt của bạn không hỗ trợ phát âm tự động.');
            }

            // Tự động reset trạng thái sau khi đọc xong
            setTimeout(() => {
                if (this.speakingWord === text && this.speakingLang === standardLang) {
                    this.speakingWord = null;
                    this.speakingLang = null;
                }
            }, 1200);
        },

        isSpeaking(text, lang = null) {
            if (lang) {
                const standardLang = (lang === 'uk' || lang === 'en-UK' || lang === 'en-GB') ? 'en-GB' : 'en-US';
                return this.speakingWord === text && this.speakingLang === standardLang;
            }
            return this.speakingWord === text;
        }
    };
}
