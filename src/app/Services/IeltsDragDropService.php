<?php

namespace App\Services;

use App\Enums\IeltsQuestionTypeEnum;
use App\Models\IeltsSubmission;
use Illuminate\Validation\ValidationException;

class IeltsDragDropService
{
    /**
     * Validate all drag-and-drop answers after applying the submitted overrides.
     * This also protects the final submit endpoint, which can bypass autosave.
     *
     * @param array<int|string, mixed> $overrides
     */
    public function validateAnswers(IeltsSubmission $submission, array $overrides = []): void
    {
        $answers = $submission->userAnswers()
            ->with('question.questionGroup')
            ->get();

        $dragAnswers = [];
        foreach ($answers as $userAnswer) {
            $question = $userAnswer->question;
            $group = $question?->questionGroup;
            if ($group?->question_type !== IeltsQuestionTypeEnum::DRAG_DROP) {
                continue;
            }

            $questionId = (string) $userAnswer->ielts_question_id;
            $answer = array_key_exists($questionId, $overrides)
                ? $overrides[$questionId]
                : (array_key_exists($userAnswer->ielts_question_id, $overrides)
                    ? $overrides[$userAnswer->ielts_question_id]
                    : $userAnswer->user_answer);

            $answer = trim((string) $answer);
            if ($answer === '') {
                continue;
            }

            $bank = data_get($group->settings, 'drag_options', []);
            if (empty($bank)) {
                $bank = $group->questions->flatMap(fn ($item) => $item->options ?? [])->all();
            }

            $allowedKeys = collect($bank)
                ->map(fn ($option) => is_array($option) ? ($option['key'] ?? null) : $option)
                ->filter(fn ($key) => is_string($key) || is_numeric($key))
                ->map(fn ($key) => $this->normalizeKey((string) $key))
                ->all();

            if (!in_array($this->normalizeKey($answer), $allowedKeys, true)) {
                throw ValidationException::withMessages([
                    "answers.{$userAnswer->ielts_question_id}" => 'Đáp án kéo thả không nằm trong ngân hàng lựa chọn.',
                ]);
            }

            $dragAnswers[] = [
                'question_id' => $userAnswer->ielts_question_id,
                'group_id' => $group->id,
                'usage' => data_get($group->settings, 'drag_option_usage', 'repeat'),
                'answer' => $answer,
            ];
        }

        $usedOnce = [];
        foreach ($dragAnswers as $dragAnswer) {
            if ($dragAnswer['usage'] !== 'once') {
                continue;
            }

            $key = $dragAnswer['group_id'] . ':' . $this->normalizeKey($dragAnswer['answer']);
            if (isset($usedOnce[$key])) {
                throw ValidationException::withMessages([
                    "answers.{$dragAnswer['question_id']}" => 'Mỗi đáp án trong ngân hàng chỉ được dùng một lần.',
                ]);
            }
            $usedOnce[$key] = true;
        }
    }

    private function normalizeKey(string $key): string
    {
        return mb_strtolower(trim($key), 'UTF-8');
    }
}
