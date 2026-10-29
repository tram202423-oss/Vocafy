<?php
namespace App\Console\Commands;

use App\Models\IeltsSubmission;
use App\Services\IeltsSpeakingRecordingService;
use App\Services\IeltsScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompleteExpiredIelts extends Command
{
    protected $signature = 'ielts:complete-expired';
    protected $description = 'Complete expired IELTS submissions using saved answers and audio checkpoints';

    public function handle(IeltsScoringService $scoring, IeltsSpeakingRecordingService $recordings): int
    {
        IeltsSubmission::where('status', 'in_progress')->where('metadata->lifecycle_version', 2)->lazyById(100)->each(function ($candidate) use ($scoring, $recordings) {
            if ($this->stillAcceptingFinalSubmission($candidate)
                || IeltsSpeakingRecordingService::canFinalize($candidate)) return;
            DB::transaction(function () use ($candidate, $scoring, $recordings): void {
                $submission = IeltsSubmission::lockForUpdate()->find($candidate->id);
                if (! $submission || $submission->status->value !== 'in_progress'
                    || $this->stillAcceptingFinalSubmission($submission)
                    || IeltsSpeakingRecordingService::canFinalize($submission)) return;
                if ($submission->skill === 'speaking') $recordings->promoteCheckpoint($submission);
                $scoring->scoreSubmission($submission);
            });
        });
        return self::SUCCESS;
    }

    private function stillAcceptingFinalSubmission(IeltsSubmission $submission): bool
    {
        $deadline = IeltsSpeakingRecordingService::deadline($submission);
        if ($submission->skill !== 'speaking') {
            $deadline->addSeconds(IeltsSubmission::FINAL_SUBMISSION_GRACE_SECONDS);
        }

        return now()->lessThan($deadline);
    }
}
