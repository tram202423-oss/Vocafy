<?php

namespace App\Services;

use App\Jobs\EvaluateIeltsSubmission;
use App\Models\IeltsSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IeltsAiAssessmentService
{
    public function request(IeltsSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $current = IeltsSubmission::lockForUpdate()->findOrFail($submission->id);
            abort_unless($current->status->value === 'completed' && in_array($current->skill, ['writing', 'speaking'], true), 409);
            $metadata = $current->metadata ?? [];
            if (in_array(data_get($metadata, 'ai_assessment.status'), ['pending', 'processing'], true)) {
                return;
            }
            $token = (string) Str::uuid();
            $metadata['ai_assessment'] = ['id' => $token, 'status' => 'pending', 'requested_at' => now()->toIso8601String()];
            $metadata[$current->skill.'_evaluation_status'] = 'pending';
            $current->update(['metadata' => $metadata]);
            EvaluateIeltsSubmission::dispatch($current->id, $token)->onConnection('ielts')->onQueue('ielts')->afterCommit();
        });
    }
}
