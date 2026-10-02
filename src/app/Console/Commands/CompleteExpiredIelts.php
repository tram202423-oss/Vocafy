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
            if (now()->lessThan(IeltsSpeakingRecordingService::deadline($candidate))
                || IeltsSpeakingRecordingService::canFinalize($candidate)) return;
            DB::transaction(function () use ($candidate, $scoring, $recordings): void {
                $submission = IeltsSubmission::lockForUpdate()->find($candidate->id);
                if (! $submission || $submission->status->value !== 'in_progress'
                    || now()->lessThan(IeltsSpeakingRecordingService::deadline($submission))
                    || IeltsSpeakingRecordingService::canFinalize($submission)) return;
                if ($submission->skill === 'speaking') $recordings->promoteCheckpoint($submission);
                $scoring->scoreSubmission($submission);
            });
        });
        return self::SUCCESS;
    }
}
