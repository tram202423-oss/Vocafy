<?php

namespace App\Services;

use App\Models\IeltsAnswerOption;
use App\Models\IeltsQuestion;
use App\Models\IeltsQuestionGroup;
use App\Models\IeltsSection;
use App\Models\IeltsSubmission;
use App\Models\IeltsTest;
use Illuminate\Support\Collection;

class IeltsExamSnapshotService
{
    public function capture(IeltsTest $test, IeltsSection $section): array
    {
        $groups = $section->questionGroups()->with(['questions', 'answerOptions'])->get();

        return [
            'version' => 1,
            'captured_at' => now()->toIso8601String(),
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'slug' => $test->slug,
                'type' => $test->type?->value ?? $test->type,
                'description' => $test->description,
            ],
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'skill' => $section->skill?->value ?? $section->skill,
                'test_type' => $section->test_type?->value ?? $section->test_type,
                'time_limit_minutes' => $section->time_limit_minutes,
                'total_questions' => $section->total_questions,
                'description' => $section->description,
                'is_active' => $section->is_active,
            ],
            'groups' => $groups->map(fn (IeltsQuestionGroup $group) => [
                'id' => $group->id,
                'title' => $group->title,
                'order' => $group->order,
                'passage_content' => $group->passage_content,
                'question_content' => $group->question_content,
                'audio_url' => $group->audio_url,
                'transcript' => $group->transcript,
                'question_type' => $group->question_type?->value ?? $group->question_type,
                'response_mode' => $group->response_mode,
                'option_usage' => $group->option_usage,
                'instruction' => $group->instruction,
                'image_url' => $group->image_url,
                'settings' => $group->settings,
                'answer_options' => $group->answerOptions->map(fn (IeltsAnswerOption $option) => [
                    'id' => $option->id,
                    'option_key' => $option->option_key,
                    'label' => $option->label,
                    'order' => $option->order,
                ])->all(),
                'questions' => $group->questions->map(fn (IeltsQuestion $question) => [
                    'id' => $question->id,
                    'question_number' => $question->question_number,
                    'order' => $question->order,
                    'prompt' => $question->prompt,
                    'explanation' => $question->explanation,
                    'quote_reference' => $question->quote_reference,
                    'options' => $question->options,
                    'correct_answer' => $question->correct_answer,
                    'word_limit' => $question->word_limit,
                    'word_limit_mode' => $question->word_limit_mode,
                    'points' => $question->points,
                    'drop_x' => $question->drop_x,
                    'drop_y' => $question->drop_y,
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * Rehydrate immutable snapshot models and attach them to the submission.
     *
     * @return array{section: IeltsSection, test: IeltsTest, groups: Collection, questions: array<string, IeltsQuestion>}|null
     */
    public function apply(IeltsSubmission $submission): ?array
    {
        $snapshot = data_get($submission->metadata, 'exam_snapshot');
        if (! is_array($snapshot) || ! is_array($snapshot['section'] ?? null)) {
            return null;
        }

        $test = new IeltsTest($snapshot['test'] ?? []);
        $test->id = $snapshot['test']['id'] ?? null;
        $test->exists = true;
        $section = new IeltsSection($snapshot['section']);
        $section->id = $snapshot['section']['id'] ?? null;
        $section->exists = true;

        $questionMap = [];
        $groups = collect($snapshot['groups'] ?? [])->map(function (array $groupData) use ($section, &$questionMap): IeltsQuestionGroup {
            $group = new IeltsQuestionGroup([
                'ielts_section_id' => $section->id,
                'title' => $groupData['title'] ?? '',
                'order' => $groupData['order'] ?? 0,
                'passage_content' => $groupData['passage_content'] ?? null,
                'question_content' => $groupData['question_content'] ?? null,
                'audio_url' => $groupData['audio_url'] ?? null,
                'transcript' => $groupData['transcript'] ?? null,
                'question_type' => $groupData['question_type'] ?? null,
                'response_mode' => $groupData['response_mode'] ?? 'standard',
                'option_usage' => $groupData['option_usage'] ?? 'repeat',
                'instruction' => $groupData['instruction'] ?? null,
                'image_url' => $groupData['image_url'] ?? null,
                'settings' => $groupData['settings'] ?? [],
            ]);
            $group->id = $groupData['id'] ?? null;
            $group->exists = true;
            $group->setRelation('section', $section);

            $questions = collect($groupData['questions'] ?? [])->map(function (array $questionData) use ($group, &$questionMap): IeltsQuestion {
                $question = new IeltsQuestion($questionData);
                $question->id = $questionData['id'] ?? null;
                $question->ielts_question_group_id = $group->id;
                $question->exists = true;
                $question->setRelation('questionGroup', $group);
                $questionMap[(string) $question->id] = $question;
                return $question;
            })->sortBy('question_number')->values();

            $options = collect($groupData['answer_options'] ?? [])->map(function (array $optionData) use ($group): IeltsAnswerOption {
                $option = new IeltsAnswerOption($optionData);
                $option->id = $optionData['id'] ?? null;
                $option->ielts_question_group_id = $group->id;
                $option->exists = true;
                return $option;
            });
            $group->setRelation('questions', $questions);
            $group->setRelation('answerOptions', $options);

            return $group;
        })->sortBy('order')->values();

        $section->setRelation('questionGroups', $groups);
        $section->setRelation('questions', $groups->flatMap(fn ($group) => $group->questions)->sortBy('question_number')->values());
        $submission->setRelation('section', $section);
        $submission->setRelation('test', $test);

        if ($submission->relationLoaded('userAnswers')) {
            foreach ($submission->userAnswers as $answer) {
                $snapshotQuestionId = (string) ($answer->question_snapshot_id ?: $answer->ielts_question_id);
                $answer->setRelation('question', $questionMap[$snapshotQuestionId] ?? null);
            }
        }

        return ['section' => $section, 'test' => $test, 'groups' => $groups, 'questions' => $questionMap];
    }
}
