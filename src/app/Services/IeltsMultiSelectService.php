<?php

namespace App\Services;

use App\Enums\IeltsQuestionTypeEnum;
use App\Models\IeltsQuestionGroup;
use App\Models\IeltsSubmission;
use Illuminate\Validation\ValidationException;

class IeltsMultiSelectService
{
    public static function enabled(IeltsQuestionGroup|array|null $group): bool
    {
        $type = data_get($group, 'question_type');

        return ($type === 'multiple_choice' || $type === IeltsQuestionTypeEnum::MULTIPLE_CHOICE)
            && (bool) data_get($group, 'settings.multi_select', false);
    }

    public static function normalize(mixed $key): string
    {
        return is_scalar($key) ? mb_strtoupper(trim((string) $key), 'UTF-8') : '';
    }

    /** Upgrade the old per-slot editor to one shared prompt and answer bank. */
    public static function hydrate(array $data): array
    {
        if (! static::enabled($data)) {
            return $data;
        }
        $questions = isset($data['id'])
            ? IeltsQuestionGroup::find($data['id'])?->questions()->get()->toArray() ?? []
            : array_values($data['questions'] ?? []);
        $first = $questions[0] ?? [];
        $defaults = [
            'start_number' => $first['question_number'] ?? 1,
            'prompt' => $first['prompt'] ?? '',
            'options' => $first['options'] ?? [],
            'correct_keys' => array_column($questions, 'correct_answer'),
            'explanation' => collect($questions)->pluck('explanation')->filter()->unique()->implode("\n\n"),
            'quote_reference' => collect($questions)->pluck('quote_reference')->filter()->unique()->implode("\n\n"),
        ];
        foreach ($defaults as $field => $value) {
            if (blank($data['settings'][$field] ?? null)) {
                $data['settings'][$field] = $value;
            }
        }

        return $data;
    }

    /** Each selected correct key generates one numbered, one-point slot. */
    public static function questions(array $group): array
    {
        if (! static::enabled($group)) {
            return $group['questions'] ?? [];
        }
        $settings = $group['settings'] ?? [];
        $keys = array_values($settings['correct_keys'] ?? []);

        return array_map(fn ($key, $index) => [
            'question_number' => (int) ($settings['start_number'] ?? 1) + $index,
            'order' => $index + 1,
            'prompt' => $settings['prompt'] ?? '',
            'options' => array_values($settings['options'] ?? []),
            'correct_answer' => (string) $key,
            'explanation' => $settings['explanation'] ?? null,
            'quote_reference' => $settings['quote_reference'] ?? null,
            'points' => 1,
        ], $keys, array_keys($keys));
    }

    public static function sync(IeltsQuestionGroup $group): void
    {
        if (! static::enabled($group) || ! array_key_exists('correct_keys', $group->settings ?? [])) {
            return;
        }
        $existing = $group->questions()->get()->values();
        // Reuse slot IDs so ordinary edits preserve saved answers and review flags.
        $questions = static::questions($group->toArray());
        foreach ($questions as $index => $data) {
            if (isset($existing[$index])) {
                $existing[$index]->update($data);
            } else {
                $group->questions()->create($data);
            }
        }
        foreach ($existing->slice(count($questions)) as $question) {
            $question->delete();
        }
        $settings = $group->settings;
        $settings['selection_limit'] = count($questions);
        $group->update(['settings' => $settings, 'response_mode' => 'standard']);
    }

    public static function options(IeltsQuestionGroup $group): array
    {
        return array_values($group->settings['options'] ?? $group->questions->first()?->options ?? []);
    }

    public static function correctKeys(IeltsQuestionGroup $group): array
    {
        return $group->questions->pluck('correct_answer')->map(fn ($key) => static::normalize($key))->unique()->values()->all();
    }

    /** Validate the final group state before writing any of its slots. */
    public function validateAnswers(IeltsSubmission $submission, array $overrides = []): void
    {
        $snapshotQuestions = app(IeltsExamSnapshotService::class)->apply($submission)['questions'] ?? [];
        $groups = $submission->userAnswers()->with('question.questionGroup.questions')->get()
            ->map(function ($answer) use ($snapshotQuestions) {
                $answer->setRelation('question', $snapshotQuestions[(string) ($answer->question_snapshot_id ?: $answer->ielts_question_id)] ?? $answer->question);
                return $answer;
            })
            ->filter(fn ($answer) => static::enabled($answer->question?->questionGroup))
            ->groupBy(fn ($answer) => $answer->question->ielts_question_group_id);

        foreach ($groups as $answers) {
            $group = $answers->first()->question->questionGroup;
            $allowed = array_map(fn ($option) => static::normalize($option['key'] ?? ''), static::options($group));
            $selected = [];
            foreach ($answers as $answer) {
                $value = array_key_exists($answer->ielts_question_id, $overrides) ? $overrides[$answer->ielts_question_id] : $answer->user_answer;
                if ($value !== null && ! is_string($value)) {
                    throw ValidationException::withMessages(['answers' => 'Mỗi ô chỉ nhận một ký hiệu đáp án.']);
                }
                $key = static::normalize($value);
                if ($key === '') {
                    continue;
                }
                if (! in_array($key, $allowed, true) || in_array($key, $selected, true)) {
                    throw ValidationException::withMessages(['answers' => 'Chọn các đáp án khác nhau trong danh sách lựa chọn.']);
                }
                $selected[] = $key;
            }
        }
    }
}
