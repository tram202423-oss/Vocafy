<?php

namespace App\Services;

use App\Models\IeltsSubmission;
use Illuminate\Validation\ValidationException;

class IeltsMatchingInformationService
{
    /**
     * Validate option usage for Matching Information answered with standard choices.
     *
     * @param array<int|string, mixed> $overrides
     */
    public function validateAnswers(IeltsSubmission $submission, array $overrides = []): void
    {
        $snapshotQuestions = app(IeltsExamSnapshotService::class)->apply($submission)['questions'] ?? [];
        $answers = $submission->userAnswers()
            ->with('question.questionGroup.questions')
            ->get()
            ->map(function ($answer) use ($snapshotQuestions) {
                $answer->setRelation('question', $snapshotQuestions[(string) ($answer->question_snapshot_id ?: $answer->ielts_question_id)] ?? $answer->question);

                return $answer;
            });

        $usedOnce = [];
        foreach ($answers as $userAnswer) {
            $question = $userAnswer->question;
            $group = $question?->questionGroup;
            if ($group?->question_type?->value !== 'matching_information' || $group->response_mode === 'drag_drop') {
                continue;
            }

            $questionId = (string) ($userAnswer->question_snapshot_id ?: $userAnswer->ielts_question_id);
            $value = array_key_exists($questionId, $overrides)
                ? $overrides[$questionId]
                : (array_key_exists($userAnswer->ielts_question_id, $overrides)
                    ? $overrides[$userAnswer->ielts_question_id]
                    : $userAnswer->user_answer);
            if ($value !== null && ! is_string($value) && ! is_numeric($value)) {
                throw ValidationException::withMessages([
                    "answers.{$questionId}" => 'Chọn một đáp án trong danh sách.',
                ]);
            }
            $key = mb_strtolower(trim((string) $value), 'UTF-8');
            if ($key === '') {
                continue;
            }

            $allowed = collect($question->options ?? [])
                ->map(fn ($option) => mb_strtolower(trim((string) (is_array($option) ? ($option['key'] ?? '') : $option)), 'UTF-8'))
                ->all();
            if (! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages([
                    "answers.{$questionId}" => 'Đáp án không nằm trong lựa chọn của câu này.',
                ]);
            }

            if (($group->option_usage ?? 'repeat') === 'once') {
                $usageKey = $group->id.':'.$key;
                if (isset($usedOnce[$usageKey])) {
                    throw ValidationException::withMessages([
                        "answers.{$questionId}" => 'Đáp án này chỉ được dùng một lần trong nhóm.',
                    ]);
                }
                $usedOnce[$usageKey] = true;
            }
        }
    }
}
