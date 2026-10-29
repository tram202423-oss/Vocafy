// Keep native MediaRecorder/MediaStream objects outside Alpine's reactive proxy.
const openDrafts = () => new Promise((resolve, reject) => {
    const request = indexedDB.open('vocafy-ielts-recordings', 1);
    request.onupgradeneeded = () => request.result.createObjectStore('drafts');
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});
async function draftStorage(key, operation, value) {
    const db = await openDrafts();
    try {
        return await new Promise((resolve, reject) => {
            const tx = db.transaction('drafts', operation === 'get' ? 'readonly' : 'readwrite');
            const store = tx.objectStore('drafts');
            const request = operation === 'put' ? store.put(value, key) : store[operation](key);
            let result;
            request.onsuccess = () => { result = request.result; };
            tx.oncomplete = () => resolve(result);
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(tx.error);
        });
    } finally { db.close(); }
}
window.ieltsRecording = function (config) {
    let recorder, stream, chunks = [], startedAt = 0, stopPromise;
    let uploadTail = Promise.resolve(), localTail = Promise.resolve(), latestBlob;
    let checkpointPending = false;
    let lastCheckpoint = 0, recordingId = config.speakingSession?.id || null;
    let revision = Number(config.speakingSession?.revision || 0);
    const headers = () => ({
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    });
    async function jsonFetch(url, options) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 45000);
        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || data.error || 'Không lưu được bản ghi. Giữ trang này và thử lưu lại.');
            return data;
        } finally { clearTimeout(timer); }
    }
    return {
        recordingState: config.speakingRecordingExists ? 'saved' : 'empty',
        recordingSeconds: Number(config.speakingRecordingDurationSeconds || 0),
        recordingError: '', recordingPreviewUrl: '', recordingTimer: null,
        speakingPlaybackUrl: config.speakingRecordingExists ? config.speakingRecordingPlaybackUrl : '',
        recordingRecoveryUrl: '', recordingCheckpointAt: '', recordingReady: false,
        async restoreSpeakingDraft() {
            if (this.skill !== 'speaking') return;
            try {
                const draft = await draftStorage(config.submissionId, 'get');
                if (draft?.recordingId === recordingId && !config.speakingSession?.finalized && draft.blob?.size) {
                    latestBlob = draft.blob;
                    revision = Math.max(revision, Number(draft.revision || 0));
                    this.recordingSeconds = draft.seconds;
                    this.setRecordingPreview(latestBlob);
                    this.recordingState = 'recoverable';
                    this.recordingError = 'Đã khôi phục bản thu trên thiết bị. Bấm Lưu lại bản thu trước khi nộp.';
                } else if (draft?.blob?.size && !config.speakingSession?.finalized) {
                    // A second tab may have replaced the server session. Preserve the old audio for download.
                    this.recordingRecoveryUrl = URL.createObjectURL(draft.blob);
                    this.recordingError = 'Có bản thu của phiên cũ trên thiết bị. Bạn có thể tải bản đó xuống.';
                }
            } catch (_) {
                this.recordingError = 'Trình duyệt không cho lưu bản dự phòng trên thiết bị. Hãy giữ trang trong lúc thu.';
            }
            if (this.recordingState !== 'recoverable' && config.speakingDraftExists && !config.speakingSession?.finalized) {
                this.recordingState = 'recoverable';
                this.recordingPreviewUrl = config.speakingRecordingPlaybackUrl + '?draft=1';
                this.recordingError = 'Có bản thu được lưu gần nhất trên server. Bấm Lưu lại bản thu để khôi phục.';
            }
            this.recordingReady = true;
        },
        setRecordingPreview(blob) {
            if (this.recordingPreviewUrl.startsWith('blob:')) URL.revokeObjectURL(this.recordingPreviewUrl);
            this.recordingPreviewUrl = URL.createObjectURL(blob);
            this.recordingRecoveryUrl = this.recordingPreviewUrl;
        },
        persistRecording(blob) {
            const value = { blob, recordingId, revision, seconds: this.recordingSeconds };
            localTail = localTail.catch(() => {}).then(() => draftStorage(config.submissionId, 'put', value));
            localTail.catch(() => {
                this.recordingError = 'Chưa lưu được bản dự phòng trên thiết bị. Hãy dừng và lưu sớm.';
            });
        },
        async startSpeakingRecording() {
            if (!this.recordingReady || ['acquiring', 'recording', 'uploading', 'stopping', 'deleting'].includes(this.recordingState) || this.isSubmitting || this.remainingSeconds <= 0) return;
            if (this.recordingState === 'recoverable' && !confirm('Thu lại sẽ thay bản đang khôi phục. Bạn đã tải bản cần giữ xuống chưa?')) return;
            this.recordingState = 'acquiring';
            this.recordingError = '';
            try {
                if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) throw new Error('Trình duyệt chưa hỗ trợ micro. Hãy dùng HTTPS hoặc localhost và cho phép truy cập micro.');
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                if (this.isSubmitting || this.remainingSeconds <= 0) throw new Error('Đã hết thời gian bắt đầu bản thu mới.');
                const session = await jsonFetch(config.speakingRecordingStartUrl, { method: 'POST', headers: headers() });
                recordingId = session.id; revision = 0; chunks = []; latestBlob = null; stopPromise = null; lastCheckpoint = 0;
                this.speakingPlaybackUrl = '';
                if (this.recordingPreviewUrl.startsWith('blob:')) URL.revokeObjectURL(this.recordingPreviewUrl);
                this.recordingPreviewUrl = ''; this.recordingRecoveryUrl = '';
                const mimeType = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'].find(type => MediaRecorder.isTypeSupported(type));
                recorder = new MediaRecorder(stream, { ...(mimeType ? { mimeType } : {}), audioBitsPerSecond: 64000 });
                recorder.ondataavailable = event => {
                    if (!event.data?.size) return;
                    chunks.push(event.data);
                    latestBlob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
                    this.recordingSeconds = Math.max(1, Math.floor((Date.now() - startedAt) / 1000));
                    this.persistRecording(latestBlob);
                    if (latestBlob.size >= 11.5 * 1024 * 1024 && this.recordingState === 'recording') {
                        this.recordingError = 'Bản ghi đã gần giới hạn 12 MB; hệ thống đang dừng và lưu.';
                        this.stopSpeakingRecording();
                    } else if (this.recordingState === 'recording' && !checkpointPending && this.recordingSeconds >= lastCheckpoint + 5) {
                        lastCheckpoint = this.recordingSeconds;
                        checkpointPending = true;
                        this.queueRecordingUpload(latestBlob, false).catch(error => {
                            this.recordingError = error.message + ' Bản thu vẫn được giữ trên thiết bị.';
                        }).finally(() => { checkpointPending = false; });
                    }
                };
                recorder.onerror = () => {
                    this.recordingError = 'Micro bị gián đoạn. Hệ thống giữ lại phần đã thu để lưu lại.';
                    this.stopSpeakingRecording();
                };
                stream.getAudioTracks().forEach(track => track.onended = () => {
                    if (this.recordingState === 'recording') this.stopSpeakingRecording();
                });
                startedAt = Date.now(); this.recordingSeconds = 0;
                this.recordingState = 'recording';
                recorder.start(1000);
                this.recordingTimer = setInterval(() => {
                    this.recordingSeconds = Math.floor((Date.now() - startedAt) / 1000);
                    if (Date.now() >= this.endTime) this.stopSpeakingRecording();
                }, 250);
            } catch (error) {
                stream?.getTracks().forEach(track => track.stop());
                this.recordingState = latestBlob ? 'recoverable' : (this.speakingPlaybackUrl ? 'saved' : 'empty');
                this.recordingError = error.message;
            }
        },
        stopSpeakingRecording() {
            if (stopPromise) return stopPromise;
            if (!recorder || recorder.state === 'inactive') {
                clearInterval(this.recordingTimer);
                stream?.getTracks().forEach(track => track.stop());
                if (latestBlob?.size) {
                    this.setRecordingPreview(latestBlob);
                    this.recordingState = 'recoverable';
                } else if (this.recordingState === 'recording') this.recordingState = 'recoverable';
                return Promise.resolve(false);
            }
            this.recordingState = 'stopping';
            clearInterval(this.recordingTimer);
            stopPromise = new Promise(resolve => {
                recorder.addEventListener('stop', async () => {
                    stream?.getTracks().forEach(track => track.stop());
                    await localTail.catch(() => {});
                    if (!latestBlob?.size) {
                        this.recordingState = 'recoverable';
                        this.recordingError = 'Không thu được âm thanh. Bạn có thể thử micro và thu lại khi còn giờ.';
                        resolve(false); return;
                    }
                    this.setRecordingPreview(latestBlob);
                    resolve(await this.retrySpeakingUpload());
                }, { once: true });
                recorder.stop();
            });
            return stopPromise;
        },
        queueRecordingUpload(blob, final) {
            const seconds = this.recordingSeconds;
            const id = recordingId;
            const upload = uploadTail.catch(() => {}).then(async () => {
                if (!final && this.recordingState !== 'recording') return null;
                revision++;
                const body = new FormData();
                const extension = blob.type.includes('ogg') ? 'ogg' : blob.type.includes('mp4') ? 'm4a' : 'webm';
                body.append('recording', blob, 'speaking.' + extension);
                body.append('duration_seconds', String(Math.max(1, seconds)));
                body.append('recording_id', id);
                body.append('revision', String(revision));
                body.append('final', final ? '1' : '0');
                const result = await jsonFetch(config.speakingRecordingUploadUrl, { method: 'POST', headers: headers(), body });
                this.recordingCheckpointAt = new Date().toLocaleTimeString();
                if (final) this.speakingPlaybackUrl = result.playback_url;
                return result;
            });
            uploadTail = upload;
            return upload;
        },
        async retrySpeakingUpload() {
            if (this.recordingState === 'uploading') return false;
            this.recordingState = 'uploading';
            try {
                if (latestBlob?.size) await this.queueRecordingUpload(latestBlob, true);
                else {
                    const result = await jsonFetch(config.speakingRecordingFinalizeUrl, { method: 'POST', headers: headers() });
                    this.speakingPlaybackUrl = result.playback_url;
                }
                this.recordingState = 'saved';
                this.recordingError = '';
                await localTail.catch(() => {});
                await draftStorage(config.submissionId, 'delete').catch(() => {});
                return true;
            } catch (error) {
                this.recordingState = 'recoverable';
                this.recordingError = error.message + ' Có thể thử lưu lại hoặc tải bản thu xuống.';
                return false;
            }
        },
        async prepareSpeakingSubmission() {
            if (['recording', 'stopping'].includes(this.recordingState)) await this.stopSpeakingRecording();
            if (this.recordingState === 'uploading') await uploadTail.catch(() => {});
            if (this.recordingState === 'recoverable') await this.retrySpeakingUpload();
        },
        async deleteSpeakingRecording() {
            if (this.isSubmitting || !['saved', 'recoverable'].includes(this.recordingState)) return;
            if (!confirm('Xóa bản ghi Speaking đã lưu và bản dự phòng của lượt này?')) return;
            const previous = this.recordingState;
            this.recordingState = 'deleting';
            try {
                await uploadTail.catch(() => {});
                await jsonFetch(config.speakingRecordingDeleteUrl, { method: 'DELETE', headers: headers() });
                await localTail.catch(() => {});
                await draftStorage(config.submissionId, 'delete').catch(() => {});
                if (this.recordingPreviewUrl.startsWith('blob:')) URL.revokeObjectURL(this.recordingPreviewUrl);
                latestBlob = null; chunks = []; recordingId = null; revision = 0; stopPromise = null;
                this.recordingPreviewUrl = ''; this.recordingRecoveryUrl = ''; this.speakingPlaybackUrl = '';
                this.recordingState = 'empty'; this.recordingSeconds = 0; this.recordingError = '';
            } catch (error) { this.recordingState = previous; this.recordingError = error.message; }
        },
        formatRecordingTime(seconds) {
            return String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
        },
    };
};
