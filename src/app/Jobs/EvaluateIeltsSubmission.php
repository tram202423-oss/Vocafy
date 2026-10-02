<?php

namespace App\Jobs;

use App\Models\IeltsSubmission;
use App\Services\IeltsScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class EvaluateIeltsSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 660;
    public bool $failOnTimeout = true;
    public function backoff(): array { return [60]; }

    public function __construct(public string $submissionId, public string $assessmentId) {}

    public function handle(IeltsScoringService $scorer): void
    {
        $submission = DB::transaction(function () {
            $submission = IeltsSubmission::lockForUpdate()->find($this->submissionId);
            if (! $submission || $submission->status->value !== 'completed') return null;
            $metadata = $submission->metadata ?? [];
            $assessment = $metadata['ai_assessment'] ?? [];
            if (($assessment['id'] ?? null) !== $this->assessmentId
                || in_array($assessment['status'] ?? '', ['graded', 'incomplete', 'ungradable'], true)) return null;
            if (($assessment['status'] ?? '') === 'processing'
                && \Illuminate\Support\Carbon::parse($assessment['started_at'])->addSeconds(720)->isFuture()) {
                $this->release(60);
                return null;
            }
            $metadata['ai_assessment'] = [...$assessment, 'status' => 'processing', 'started_at' => now()->toIso8601String()];
            $metadata[$submission->skill.'_evaluation_status'] = 'processing';
            $submission->update(['metadata' => $metadata]);
            return $submission;
        });
        if (! $submission) return;

        try {
            $result = $submission->skill === 'writing'
                ? $scorer->scoreWritingSubmission($submission)
                : $scorer->scoreSpeakingSubmission($submission);
            $status = data_get($result->metadata, $submission->skill.'_evaluation_status', 'failed');
            $this->setStatus($status);
            if ($status === 'failed') throw new \RuntimeException('IELTS AI assessment failed; see evaluation status.');
        } catch (Throwable $error) {
            $this->setStatus('failed');
            throw $error;
        }
    }

    public function failed(?Throwable $exception): void { $this->setStatus('failed'); }

    private function setStatus(string $status): void
    {
        DB::transaction(function () use ($status): void {
            $submission = IeltsSubmission::lockForUpdate()->find($this->submissionId);
            if (! $submission || data_get($submission->metadata, 'ai_assessment.id') !== $this->assessmentId) return;
            $metadata = $submission->metadata;
            $metadata['ai_assessment']['status'] = $status;
            $metadata['ai_assessment']['finished_at'] = now()->toIso8601String();
            $metadata[$submission->skill.'_evaluation_status'] = $status;
            $submission->update(['metadata' => $metadata]);
        });
    }
}
