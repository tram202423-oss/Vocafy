<?php
namespace App\Services;

use App\Models\IeltsSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class IeltsSpeakingRecordingService
{
    public static function deadline(IeltsSubmission $submission): Carbon
    {
        return Carbon::parse(data_get($submission->metadata, 'deadline_at')
            ?? $submission->started_at->copy()->addMinutes($submission->section?->time_limit_minutes ?: 60));
    }

    public static function canFinalize(IeltsSubmission $submission): bool
    {
        return $submission->skill === 'speaking'
            && filled(data_get($submission->metadata, 'speaking_session.id'))
            && ! data_get($submission->metadata, 'speaking_session.finalized', false)
            && now()->lessThanOrEqualTo(static::deadline($submission)->addSeconds(120));
    }

    private function guard(IeltsSubmission $submission, bool $finalizing = false): void
    {
        abort_unless($submission->skill === 'speaking' && $submission->status->value === 'in_progress', 409, 'Lượt Speaking đã kết thúc.');
        abort_if(now()->greaterThanOrEqualTo(static::deadline($submission)) && (! $finalizing || ! static::canFinalize($submission)), 409, 'Đã hết thời gian lưu bản ghi.');
    }

    public function start(string $id): array
    {
        return DB::transaction(function () use ($id): array {
            $submission = IeltsSubmission::lockForUpdate()->findOrFail($id);
            $this->guard($submission);
            $metadata = $submission->metadata ?? [];
            $old = data_get($metadata, 'speaking_draft.path');
            unset($metadata['speaking_draft']);
            $metadata['speaking_session'] = [
                'id' => (string) Str::uuid(), 'started_at' => now()->toIso8601String(),
                'revision' => 0, 'finalized' => false,
            ];
            $submission->update(['metadata' => $metadata]);
            $this->deleteAfterCommit($old);
            return $metadata['speaking_session'];
        });
    }

    public function upload(string $id, UploadedFile $file, array $data): array
    {
        $mime = match (strtolower((string) $file->getMimeType())) {
            'video/webm' => 'audio/webm', 'audio/x-wav' => 'audio/wav',
            'application/ogg' => 'audio/ogg', 'audio/x-m4a' => 'audio/mp4',
            default => strtolower((string) $file->getMimeType()),
        };
        $extension = match ($mime) {
            'audio/ogg' => 'ogg', 'audio/mp4', 'audio/m4a' => 'm4a',
            'audio/mpeg' => 'mp3', 'audio/wav' => 'wav', 'audio/aac' => 'aac',
            default => 'webm',
        };
        $path = $file->storeAs('ielts/speaking/'.$id, Str::uuid().'.'.$extension, 'local');
        abort_unless($path, 500, 'Không lưu được tệp âm thanh.');
        try {
            $result = DB::transaction(function () use ($id, $path, $mime, $extension, $file, $data): array {
                $submission = IeltsSubmission::lockForUpdate()->findOrFail($id);
                $metadata = $submission->metadata ?? [];
                $session = $metadata['speaking_session'] ?? [];
                abort_unless(($session['id'] ?? null) === $data['recording_id'], 409, 'Bản thu đã được thay bằng phiên khác. Hãy tải lại trang.');
                if (($session['finalized'] ?? false) && $data['final'] && $submission->status->value === 'in_progress') {
                    $savedPath = data_get($metadata, 'speaking_recording.path');
                    $disk = Storage::disk('local');
                    if ($savedPath && $disk->exists($savedPath)
                        && hash_equals(hash_file('sha256', $disk->path($savedPath)), hash_file('sha256', $disk->path($path)))) {
                        $this->deleteAfterCommit($path);
                        return ['status' => 'saved', 'revision' => $session['revision'],
                            'playback_url' => route('ielts.exam.speaking-recording', $id)];
                    }
                }
                $this->guard($submission, true);
                abort_if($session['finalized'] ?? false, 409, 'Bản thu này đã được chốt.');
                abort_if((int) $data['revision'] <= (int) ($session['revision'] ?? 0), 409, 'Phiên bản bản ghi đã cũ.');
                $oldDraft = data_get($metadata, 'speaking_draft.path');
                $recording = [
                    'disk' => 'local', 'path' => $path, 'mime_type' => $mime, 'extension' => $extension,
                    'size_bytes' => $file->getSize(), 'duration_seconds' => (int) $data['duration_seconds'],
                    'uploaded_at' => now()->toIso8601String(),
                ];
                $metadata['speaking_session']['revision'] = (int) $data['revision'];
                if ($data['final']) {
                    $oldFinal = data_get($metadata, 'speaking_recording.path');
                    $metadata['speaking_recording'] = $recording;
                    $metadata['speaking_session']['finalized'] = true;
                    $metadata['speaking_evaluation_status'] = 'pending';
                    unset($metadata['speaking_draft'], $metadata['speaking_evaluation']);
                    $this->deleteAfterCommit($oldFinal);
                } else {
                    $metadata['speaking_draft'] = $recording;
                }
                $submission->update(['metadata' => $metadata]);
                $this->deleteAfterCommit($oldDraft);
                return ['status' => $data['final'] ? 'saved' : 'checkpoint',
                    'revision' => (int) $data['revision'], 'playback_url' => route('ielts.exam.speaking-recording', $id)];
            });
            return $result;
        } catch (Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    // Caller must hold the submission lock. Used both for recovery and expiry.
    public function promoteCheckpoint(IeltsSubmission $submission): bool
    {
        $metadata = $submission->metadata ?? [];
        $draft = $metadata['speaking_draft'] ?? null;
        if (! is_array($draft) || ! Storage::disk('local')->exists($draft['path'] ?? '')) {
            // An interrupted retake must never silently submit an older recording.
            if (filled(data_get($metadata, 'speaking_session.id')) && ! data_get($metadata, 'speaking_session.finalized', false)) {
                if (isset($metadata['speaking_recording'])) $metadata['speaking_previous_recording'] = $metadata['speaking_recording'];
                unset($metadata['speaking_recording']);
                $metadata['speaking_session']['abandoned'] = true;
                $submission->update(['metadata' => $metadata]);
            }
            return false;
        }
        $oldFinal = data_get($metadata, 'speaking_recording.path');
        $metadata['speaking_recording'] = $draft;
        $metadata['speaking_session']['finalized'] = true;
        $metadata['speaking_session']['recovered'] = true;
        unset($metadata['speaking_draft'], $metadata['speaking_evaluation']);
        $submission->update(['metadata' => $metadata]);
        $this->deleteAfterCommit($oldFinal);
        return true;
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $submission = IeltsSubmission::lockForUpdate()->findOrFail($id);
            $this->guard($submission);
            $metadata = $submission->metadata ?? [];
            $paths = [data_get($metadata, 'speaking_recording.path'), data_get($metadata, 'speaking_draft.path')];
            unset($metadata['speaking_recording'], $metadata['speaking_session'], $metadata['speaking_draft'], $metadata['speaking_evaluation']);
            $metadata['speaking_evaluation_status'] = 'missing';
            $submission->update(['metadata' => $metadata]);
            foreach ($paths as $path) $this->deleteAfterCommit($path);
        });
    }

    private function deleteAfterCommit(?string $path): void
    {
        if (! $path) return;
        DB::afterCommit(function () use ($path): void {
            try { Storage::disk('local')->delete($path); }
            catch (Throwable $error) { report($error); }
        });
    }
}
