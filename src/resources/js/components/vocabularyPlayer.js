import { speakText } from '../utils/speech';

/**
 * Alpine.js Component: vocabularyPlayer
 * Quản lý trạng thái và thao tác phát âm từ vựng & câu ví dụ
 */
export default function vocabularyPlayer() {
    return {
        speakingWord: null,

        speak(text) {
            this.speakingWord = text;
            const success = speakText(text);

            if (!success && !('speechSynthesis' in window)) {
                alert('Trình duyệt của bạn không hỗ trợ phát âm tự động.');
            }

            // Tự động reset trạng thái sau khi đọc xong
            setTimeout(() => {
                this.speakingWord = null;
            }, 1200);
        },

        isSpeaking(text) {
            return this.speakingWord === text;
        }
    };
}
