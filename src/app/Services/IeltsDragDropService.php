<?php

namespace App\Services;

use App\Enums\IeltsQuestionTypeEnum;
use App\Models\IeltsQuestionGroup;
use App\Models\IeltsSubmission;
use Illuminate\Validation\ValidationException;

class IeltsDragDropService
{
    public static function enabled(?IeltsQuestionGroup $group): bool
    {
        return $group !== null && (
            $group->response_mode === 'drag_drop'
            || $group->question_type === IeltsQuestionTypeEnum::DRAG_DROP
            || $group->question_type?->value === IeltsQuestionTypeEnum::DRAG_DROP->value
        );
    }

    /** @return array<int, string> */
    public function optionKeys(IeltsQuestionGroup $group): array
    {
        $keys = $group->answerOptions->pluck('option_key')->all();
        if ($keys === []) {
            $bank = data_get($group->settings, 'drag_options', []);
            $keys = collect($bank)->map(fn ($option) => is_array($option)
                ? ($option['key'] ?? null)
                : $option)->filter(fn ($key) => is_string($key) || is_numeric($key))->all();
        }
        if ($keys === []) {
            $keys = $group->questions->flatMap(fn ($question) => collect($question->options ?? [])
                ->map(fn ($option) => is_array($option) ? ($option['key'] ?? null) : null))->all();
        }

        return collect($keys)->filter(fn ($key) => is_string($key) || is_numeric($key))
            ->map(fn ($key) => (string) $key)->values()->all();
    }

    /**
     * Validate the complete final drag-and-drop state after applying overrides.
     *
     * @param array<int|string, mixed> $overrides
     */
    public function validateAnswers(IeltsSubmission $submission, array $overrides = []): void
    {
        $snapshotQuestions = app(IeltsExamSnapshotService::class)->apply($submission)['questions'] ?? [];
        $answers = $submission->userAnswers()
            ->with('question.questionGroup.answerOptions', 'question.questionGroup.questions')
            ->get()
            ->map(function ($answer) use ($snapshotQuestions) {
                $answer->setRelation('question', $snapshotQuestions[(string) ($answer->question_snapshot_id ?: $answer->ielts_question_id)] ?? $answer->question);
                return $answer;
            });

        $usedOnce = [];
        foreach ($answers as $userAnswer) {
            $question = $userAnswer->question;
            $group = $question?->questionGroup;
            if (! static::enabled($group)) {
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
                    "answers.{$questionId}" => 'Mỗi ô kéo thả chỉ nhận một key trong ngân hàng.',
                ]);
            }
            $answer = trim((string) $value);
            if ($answer === '') {
                continue;
            }

            $allowedKeys = array_map(fn ($key) => $this->normalizeKey($key), $this->optionKeys($group));
            if (! in_array($this->normalizeKey($answer), $allowedKeys, true)) {
                throw ValidationException::withMessages([
                    "answers.{$questionId}" => 'Đáp án kéo thả không nằm trong ngân hàng lựa chọn.',
                ]);
            }

            $usage = $group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat');
            if ($usage === 'once') {
                $key = $group->id.':'.$this->normalizeKey($answer);
                if (isset($usedOnce[$key])) {
                    throw ValidationException::withMessages([
                        "answers.{$questionId}" => 'Mỗi đáp án trong ngân hàng chỉ được dùng một lần.',
                    ]);
                }
                $usedOnce[$key] = true;
            }
        }
    }

    private function normalizeKey(string $key): string
    {
        return mb_strtolower(trim($key), 'UTF-8');
    }
}
