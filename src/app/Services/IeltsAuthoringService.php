<?php

namespace App\Services;

use App\Enums\IeltsQuestionTypeEnum;
use App\Models\IeltsSection;
use App\Models\IeltsTest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IeltsAuthoringService
{
    public static function isDragDrop(array $group): bool
    {
        return ($group['response_mode'] ?? 'standard') === 'drag_drop'
            || ($group['question_type'] ?? '') === IeltsQuestionTypeEnum::DRAG_DROP->value;
    }

    public static function supportsDragDrop(?string $type): bool
    {
        return in_array($type, ['matching_headings', 'matching_information', 'fill_in_blanks', 'map_labeling', 'drag_drop'], true);
    }

    public static function needsOptions(array $group): bool
    {
        return ! static::isDragDrop($group)
            && in_array($group['question_type'] ?? '', ['multiple_choice', 'matching_headings', 'matching_information', 'map_labeling'], true);
    }

    public static function fixedAnswers(?string $type): array
    {
        return match ($type) {
            'true_false_not_given' => ['TRUE' => 'TRUE', 'FALSE' => 'FALSE', 'NOT GIVEN' => 'NOT GIVEN'],
            'yes_no_not_given' => ['YES' => 'YES', 'NO' => 'NO', 'NOT GIVEN' => 'NOT GIVEN'],
            default => [],
        };
    }

    public static function prepareQuestion(array $data, ?string $type): array
    {
        $data['prompt'] ??= '';
        $fixed = static::fixedAnswers($type);
        if ($fixed) {
            $data['options'] = collect($fixed)->map(fn ($label, $key) => ['key' => $key, 'text' => $label])->values()->all();
        }

        return $data;
    }

    public static function blankNumbers(?string $content): array
    {
        preg_match_all('/\[blank_(\d+)\]/', $content ?? '', $matches);

        return array_map('intval', $matches[1]);
    }

    /** Append entries without replacing existing relationship item keys. */
    public static function appendOptions(array $existing, string $text): array
    {
        $keys = array_map(fn ($option) => mb_strtolower(trim($option['option_key'] ?? '')), $existing);
        foreach (preg_split('/\R/u', trim($text)) as $index => $line) {
            if (trim($line) === '') {
                continue;
            }
            $parts = preg_split('/\s*[|\t]\s*/u', $line, 2);
            $key = trim($parts[0]);
            $label = trim($parts[1] ?? '');
            if (! preg_match('/^[\p{L}\p{N}_-]+$/u', $key) || $label === '') {
                throw ValidationException::withMessages(['data.entries' => 'Dòng '.($index + 1).': dùng định dạng Ký hiệu | Nội dung, ví dụ A | morning.']);
            }
            if (in_array(mb_strtolower($key), $keys, true)) {
                throw ValidationException::withMessages(['data.entries' => "Ký hiệu {$key} bị trùng. Hãy sửa trước khi thêm."]);
            }
            $keys[] = mb_strtolower($key);
            $existing[(string) Str::uuid()] = ['option_key' => $key, 'label' => $label];
        }

        return $existing;
    }

    public static function appendQuestions(array $existing, array $numbers): array
    {
        $used = array_map(fn ($question) => (int) ($question['question_number'] ?? 0), $existing);
        foreach ($numbers as $number) {
            if (in_array((int) $number, $used, true)) {
                continue;
            }
            $existing[(string) Str::uuid()] = [
                'question_number' => (int) $number,
                'prompt' => '',
                'correct_answer' => null,
                'options' => [],
                'points' => 1,
            ];
            $used[] = (int) $number;
        }

        return $existing;
    }

    /** Cross-group checks run before any relationship writes. */
    public static function validateGroups(array $groups, string $skill, string $path): void
    {
        $errors = [];
        $usedNumbers = [];
        foreach ($groups as $groupKey => $group) {
            $base = "{$path}.{$groupKey}";
            $drag = static::isDragDrop($group);
            $type = $group['question_type'] ?? '';
            $multi = IeltsMultiSelectService::enabled($group);
            $questions = IeltsMultiSelectService::questions($group);
            if ($multi) {
                $settings = $group['settings'] ?? [];
                $keys = array_map([IeltsMultiSelectService::class, 'normalize'], $settings['correct_keys'] ?? []);
                if (! in_array($skill, ['reading', 'listening'], true)) {
                    $errors["{$base}.settings.multi_select"] = 'Chọn nhiều đáp án chỉ dùng cho Reading và Listening.';
                }
                if (count($keys) < 2 || count($keys) !== count(array_unique($keys))) {
                    $errors["{$base}.settings.correct_keys"] = 'Chọn ít nhất hai đáp án đúng khác nhau.';
                }
                $start = filter_var($settings['start_number'] ?? null, FILTER_VALIDATE_INT);
                if (! $start || $start < 1 || $start + count($keys) - 1 > 200) {
                    $errors["{$base}.settings.start_number"] = 'Dãy số câu phải nằm trong khoảng 1–200.';
                }
            }
            $bank = $group['answerOptions'] ?? [];
            $bankKeys = [];

            if ($drag && (! in_array($skill, ['reading', 'listening'], true) || ! static::supportsDragDrop($type))) {
                $errors["{$base}.response_mode"] = 'Kéo thả chỉ dùng cho Reading/Listening và dạng nối, điền từ hoặc gán nhãn.';
            }
            foreach ($bank as $optionKey => $option) {
                $key = mb_strtolower(trim($option['option_key'] ?? ''));
                if ($key !== '' && in_array($key, $bankKeys, true)) {
                    $errors["{$base}.answerOptions.{$optionKey}.option_key"] = 'Ký hiệu trong cùng ngân hàng không được trùng.';
                }
                $bankKeys[] = $key;
            }
            if ($drag && count($questions) && ! count($bank)) {
                $errors["{$base}.answerOptions"] = 'Thêm các lựa chọn vào ngân hàng trước khi chọn đáp án đúng.';
            }
            $content = $group['question_content'] ?? '';
            if (! $content && $drag) {
                $content = $group['passage_content'] ?? '';
            }
            $blanks = static::blankNumbers($content);
            $numbers = array_map(fn ($question) => (int) ($question['question_number'] ?? 0), $questions);
            if ($drag && count($blanks)) {
                if (count($blanks) !== count(array_unique($blanks)) || array_diff($blanks, $numbers) || array_diff($numbers, $blanks)) {
                    $errors["{$base}.question_content"] = 'Mỗi [blank_N] phải khớp đúng một câu số N trong nhóm; không trùng hoặc thiếu ô.';
                }
            }
            $usedAnswers = [];
            foreach ($questions as $questionKey => $question) {
                $qPath = $multi ? "{$base}.settings" : "{$base}.questions.{$questionKey}";
                $number = (int) ($question['question_number'] ?? 0);
                if (isset($usedNumbers[$number])) {
                    $numberField = $multi ? 'start_number' : 'question_number';
                    $errors["{$qPath}.{$numberField}"] = "Câu {$number} đã có trong nhóm khác hoặc trong nhóm này.";
                }
                $usedNumbers[$number] = true;
                if (trim($question['prompt'] ?? '') === '' && ! ($drag && in_array($number, $blanks, true))) {
                    $errors["{$qPath}.prompt"] = 'Nhập nội dung câu hỏi, hoặc tạo ô [blank_N] tương ứng trong đề kéo thả.';
                }
                // Writing/Speaking are assessed separately; no answer key is needed.
                if (! in_array($skill, ['reading', 'listening'], true)) {
                    continue;
                }
                $answer = trim($question['correct_answer'] ?? '');
                if ($drag) {
                    $normalized = mb_strtolower($answer);
                    if (! in_array($normalized, $bankKeys, true) || $normalized === '') {
                        $errors["{$qPath}.correct_answer"] = 'Chọn đáp án có trong ngân hàng của nhóm này.';
                    }
                    if (($group['option_usage'] ?? 'repeat') === 'once' && isset($usedAnswers[$normalized])) {
                        $errors["{$qPath}.correct_answer"] = 'Đáp án này đã dùng cho câu khác. Chọn đáp án khác hoặc cho phép dùng lại.';
                    }
                    $usedAnswers[$normalized] = true;
                } elseif (static::needsOptions($group)) {
                    $optionKeys = array_column($question['options'] ?? [], 'key');
                    if (! count($optionKeys) || count($optionKeys) !== count(array_unique(array_map(fn ($key) => mb_strtolower(trim($key)), $optionKeys)))) {
                        $errors["{$qPath}.options"] = 'Thêm các lựa chọn có ký hiệu khác nhau.';
                    }
                    if (! in_array($answer, $optionKeys, true)) {
                        $answerField = $multi ? 'correct_keys' : 'correct_answer';
                        $errors["{$qPath}.{$answerField}"] = 'Chọn đáp án trong các lựa chọn của câu hỏi.';
                    }
                }
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function syncSectionTotals(IeltsSection $section): void
    {
        // The multi-select editor hides the per-question relationship repeater.
        // Materialize its slots after all group settings have been saved.
        foreach ($section->questionGroups()->get() as $group) {
            IeltsMultiSelectService::sync($group);
        }
        $section->update(['total_questions' => $section->questions()->count()]);
        foreach ($section->tests()->get() as $test) {
            static::syncTestSections($test);
        }
    }

    public static function syncTestSections(IeltsTest $test): void
    {
        $sections = $test->sections()->get()->sortBy(fn ($section) => array_search($section->skill->value, ['listening', 'reading', 'writing', 'speaking'], true));
        foreach ($sections->values() as $index => $section) {
            $test->sections()->updateExistingPivot($section->id, ['order' => $index + 1]);
        }
        $test->update(['total_questions' => $sections->sum('total_questions')]);
    }
}
