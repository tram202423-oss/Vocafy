<?php

namespace App\Services;

use App\Enums\IeltsQuestionTypeEnum;
use App\Models\IeltsQuestion;
use App\Models\IeltsSubmission;
use Illuminate\Validation\ValidationException;

class IeltsWordLimitService
{
    public static function appliesTo(?IeltsQuestion $question): bool
    {
        $group = $question?->questionGroup;
        $type = $group?->question_type?->value ?? $group?->question_type;

        return $question !== null
            && static::rule($question)['limit'] > 0
            && empty($question->options)
            && in_array($group?->section?->skill?->value, ['reading', 'listening'], true)
            && ($group->response_mode ?? 'standard') === 'standard'
            && in_array($type, [
                IeltsQuestionTypeEnum::FILL_IN_BLANKS->value,
                IeltsQuestionTypeEnum::SHORT_ANSWER->value,
                IeltsQuestionTypeEnum::MAP_LABELING->value,
            ], true);
    }

    public static function rule(IeltsQuestion $question): array
    {
        $mode = $question->word_limit_mode;
        $limit = (int) $question->word_limit;
        if (! $mode) {
            $instruction = strtoupper(strip_tags($question->questionGroup?->instruction ?? ''));
            if (preg_match('/(ONE|TWO|THREE|FOUR|[1-9]) WORDS? (AND\/OR (?:A |ONE )?NUMBER|ONLY)/', $instruction, $match)) {
                $limit = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4][$match[1]] ?? (int) $match[1];
                $mode = $match[2] === 'ONLY' ? 'words' : 'words_and_number';
            }
        }
        return ['mode' => $mode ?: 'tokens', 'limit' => $limit];
    }

    private static function tokens(?string $answer): array
    {
        $answer = trim((string) $answer);
        if ($answer === '') return [];
        // A spaced phone number is one number; punctuation inside emails/decimals is not a new word.
        if (preg_match('/^\+?[0-9][0-9 ()-]+$/', $answer) && strlen(preg_replace('/\D/', '', $answer)) >= 5) return [$answer];
        return preg_split('/\s+/u', $answer, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public static function wordCount(?string $answer): int { return count(static::tokens($answer)); }

    public static function label(IeltsQuestion $question): string
    {
        $rule = static::rule($question);
        return match ($rule['mode']) {
            'words' => "Tối đa {$rule['limit']} từ, không dùng số",
            'words_and_number' => "Tối đa {$rule['limit']} từ và/hoặc một số",
            default => "Tối đa {$rule['limit']} từ/số",
        };
    }

    public static function isWithinLimit(?IeltsQuestion $question, ?string $answer): bool
    {
        if (! static::appliesTo($question) || trim((string) $answer) === '') return true;
        $rule = static::rule($question);
        $tokens = static::tokens($answer);
        $numbers = count(array_filter($tokens, fn ($token) => preg_match('/^[£$€]?[+-]?[0-9][0-9., ()%-]*$/u', $token)));
        return match ($rule['mode']) {
            'words' => $numbers === 0 && count($tokens) <= $rule['limit'],
            'words_and_number' => $numbers <= 1 && count($tokens) - $numbers <= $rule['limit'],
            default => count($tokens) <= $rule['limit'],
        };
    }

    /** Validate final typed-answer values after applying request overrides. */
    public function validateAnswers(IeltsSubmission $submission, array $overrides = []): void
    {
        $snapshotQuestions = app(IeltsExamSnapshotService::class)->apply($submission)['questions'] ?? [];
        $answers = $submission->userAnswers()->with('question.questionGroup.section')->get()
            ->map(function ($answer) use ($snapshotQuestions) {
                $answer->setRelation('question', $snapshotQuestions[(string) ($answer->question_snapshot_id ?: $answer->ielts_question_id)] ?? $answer->question);
                return $answer;
            });

        foreach ($answers as $userAnswer) {
            $questionId = (string) ($userAnswer->question_snapshot_id ?: $userAnswer->ielts_question_id);
            $value = array_key_exists($questionId, $overrides)
                ? $overrides[$questionId]
                : (array_key_exists($userAnswer->ielts_question_id, $overrides)
                    ? $overrides[$userAnswer->ielts_question_id]
                    : $userAnswer->user_answer);

            if ($value !== null && ! is_string($value)) {
                throw ValidationException::withMessages([
                    "answers.{$questionId}" => 'Câu trả lời phải là văn bản.',
                ]);
            }
            if (! static::isWithinLimit($userAnswer->question, $value)) {
                $message = static::label($userAnswer->question);
                throw ValidationException::withMessages([
                    "answers.{$questionId}" => $message . '.',
                ]);
            }
        }
    }
}
