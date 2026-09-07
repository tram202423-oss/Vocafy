import { speakText } from '../utils/speech';

/**
 * Alpine.js Component: vocabularyLearning
 * Quản lý tiến độ học từ vựng: trạng thái (new/learning/mastered),
 * phát âm, và gọi API ghi nhận tiến độ.
 *
 * @param {Object} initialProgressMap  - Map { vocabulary_id: status } từ server
 * @param {Object} initialSummary      - { total, learning, mastered }
 * @param {string} csrfToken           - Laravel CSRF token
 */
export default function vocabularyLearning(initialProgressMap = {}, initialSummary = {}, csrfToken = '', isAuthenticated = false) {
    return {
        // ─── State ───────────────────────────────────────────────────────────
        speakingWord: null,
        isAuthenticated: !!isAuthenticated,
        statusMap: initialProgressMap,     // { vocab_id: 'new'|'learning'|'mastered' }
        summary: {
            total:    initialSummary.total    ?? 0,
            learning: initialSummary.learning ?? 0,
            mastered: initialSummary.mastered ?? 0,
        },
        loadingId: null,   // ID của từ đang được gọi API (hiển thị loading)
        toastMsg: null,    // Thông báo nhanh sau khi đánh dấu

        // ─── Computed ────────────────────────────────────────────────────────
        get progressPercent() {
            if (!this.summary.total) return 0;
            return Math.round((this.summary.mastered / this.summary.total) * 100);
        },

        get learnedPercent() {
            if (!this.summary.total) return 0;
            return Math.round(((this.summary.learning + this.summary.mastered) / this.summary.total) * 100);
        },

        // ─── Helpers ─────────────────────────────────────────────────────────
        getStatus(vocabId) {
            return this.statusMap[vocabId] ?? 'new';
        },

        isMastered(vocabId) {
            return this.getStatus(vocabId) === 'mastered';
        },

        isLearning(vocabId) {
            return this.getStatus(vocabId) === 'learning';
        },

        showToast(msg) {
            this.toastMsg = msg;
            setTimeout(() => { this.toastMsg = null; }, 2500);
        },

        // ─── Speech ──────────────────────────────────────────────────────────
        speak(text) {
            this.speakingWord = text;
            const success = speakText(text);

            if (!success && !('speechSynthesis' in window)) {
                alert('Trình duyệt của bạn không hỗ trợ phát âm tự động.');
            }

            setTimeout(() => { this.speakingWord = null; }, 1200);
        },

        isSpeaking(text) {
            return this.speakingWord === text;
        },

        // ─── API calls ───────────────────────────────────────────────────────
        async _post(url) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then(r => {
                if (r.status === 401) {
                    return { success: false, unauthenticated: true };
                }
                return r.json();
            });
        },

        /**
         * Ghi nhận user vừa xem từ này (new → learning).
         * Chỉ gọi khi user đã đăng nhập.
         */
        async markReview(vocabId) {
            if (!this.isAuthenticated || !csrfToken || this.isMastered(vocabId)) return;

            const oldStatus = this.getStatus(vocabId);
            // Optimistic UI
            if (oldStatus === 'new') {
                this.statusMap[vocabId] = 'learning';
                this.summary.learning++;
            }

            try {
                await this._post(`/vocabulary/${vocabId}/review`);
            } catch (e) {
                // Rollback nếu lỗi
                if (oldStatus === 'new') {
                    this.statusMap[vocabId] = 'new';
                    this.summary.learning--;
                }
            }
        },

        /**
         * Kiểm tra từ người dùng gõ vào so với từ tiếng Anh gốc:
         * - Đúng: gọi markMastered() và trả về { success: true }
         * - Sai: trả về { success: false, message: ... }
         */
        checkAndMaster(vocabId, inputWord, targetWord) {
            const entered = (inputWord || '').trim().toLowerCase();
            const target = (targetWord || '').trim().toLowerCase();

            if (!entered) {
                return { success: false, message: 'Vui lòng nhập từ tiếng Anh!' };
            }

            if (entered === target) {
                this.markMastered(vocabId);
                this.showToast(`🎉 Chính xác! Bạn đã thuộc từ "${targetWord}".`);
                return { success: true };
            }

            return { 
                success: false, 
                message: 'Chưa chính xác, hãy thử lại nhé!' 
            };
        },

        /**
         * Đánh dấu từ đã thuộc hoàn toàn (→ mastered).
         */
        async markMastered(vocabId) {
            if (!this.isAuthenticated || !csrfToken || this.loadingId === vocabId) return;

            this.loadingId = vocabId;
            const oldStatus = this.getStatus(vocabId);

            // Optimistic UI
            this.statusMap[vocabId] = 'mastered';
            if (oldStatus === 'learning') this.summary.learning--;
            if (oldStatus !== 'mastered') this.summary.mastered++;

            try {
                await this._post(`/vocabulary/${vocabId}/master`);
            } catch (e) {
                // Rollback
                this.statusMap[vocabId] = oldStatus;
                if (oldStatus === 'learning') this.summary.learning++;
                if (oldStatus !== 'mastered') this.summary.mastered--;
                this.showToast('❌ Có lỗi xảy ra, thử lại sau.');
            }

            this.loadingId = null;
        },

        /**
         * Đặt lại trạng thái từ về "new" (học lại từ đầu).
         */
        async markReset(vocabId) {
            if (!this.isAuthenticated || !csrfToken || this.loadingId === vocabId) return;

            this.loadingId = vocabId;
            const oldStatus = this.getStatus(vocabId);

            // Optimistic UI
            this.statusMap[vocabId] = 'new';
            if (oldStatus === 'learning') this.summary.learning--;
            if (oldStatus === 'mastered') this.summary.mastered--;

            try {
                await this._post(`/vocabulary/${vocabId}/reset`);
                this.showToast('🔄 Đặt lại — bắt đầu học lại!');
            } catch (e) {
                // Rollback
                this.statusMap[vocabId] = oldStatus;
                if (oldStatus === 'learning') this.summary.learning++;
                if (oldStatus === 'mastered') this.summary.mastered++;
            }

            this.loadingId = null;
        },
    };
}
