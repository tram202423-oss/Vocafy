<?php

namespace App\Services;

use App\Models\IeltsSubmission;
use Illuminate\Validation\ValidationException;

class IeltsMultiSelectService
{
    /**
     * Validate one-answer-per-slot multi-select groups after applying overrides.
     *
     * @param array<int|string, mixed> $overrides
     */
    public function validateAnswers(IeltsSubmission $submission, array $overrides = []): void
    {
        $answers = $submission->userAnswers()
            ->with('question.questionGroup')
            ->get();

        $groups = $answers
            ->filter(fn ($answer) => data_get($answer->question?->questionGroup?->settings, 'multi_select', false))
            ->groupBy(fn ($answer) => $answer->question->questionGroup->id);

        foreach ($groups as $groupAnswers) {
            $group = $groupAnswers->first()->question->questionGroup;
            $limit = (int) data_get($group->settings, 'selection_limit', $groupAnswers->count());
            $limit = max(1, min($limit, $groupAnswers->count()));
            $options = $groupAnswers->first()->question->options ?? [];
            $allowedKeys = collect($options)
                ->map(fn ($option) => is_array($option) ? ($option['key'] ?? null) : $option)
                ->filter(fn ($key) => is_string($key) || is_numeric($key))
                ->map(fn ($key) => mb_strtolower((string) $key, 'UTF-8'))
                ->all();

            $selected = [];
            foreach ($groupAnswers as $userAnswer) {
                $questionId = (string) $userAnswer->ielts_question_id;
                $value = array_key_exists($questionId, $overrides)
                    ? $overrides[$questionId]
                    : (array_key_exists($userAnswer->ielts_question_id, $overrides)
                        ? $overrides[$userAnswer->ielts_question_id]
                        : $userAnswer->user_answer);
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                if (!in_array(mb_strtolower($value, 'UTF-8'), $allowedKeys, true)) {
                    throw ValidationException::withMessages([
                        "answers.{$userAnswer->ielts_question_id}" => 'Hãy chọn một đáp án trong danh sách lựa chọn.',
                    ]);
                }

                $selected[] = ['question_id' => $userAnswer->ielts_question_id, 'value' => mb_strtolower($value, 'UTF-8')];
            }

            if (count($selected) > $limit) {
                throw ValidationException::withMessages([
                    'answers' => "Nhóm câu hỏi này chỉ cho phép chọn tối đa {$limit} đáp án.",
                ]);
            }

            if (count(array_unique(array_column($selected, 'value'))) !== count($selected)) {
                throw ValidationException::withMessages([
                    'answers' => 'Không thể chọn cùng một đáp án nhiều lần trong một nhóm.',
                ]);
            }
        }
    }
}
