<?php

namespace Database\Seeders;

use App\Models\IeltsBandScore;
use App\Models\IeltsSection;
use App\Models\IeltsTest;
use App\Services\IeltsAuthoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Import only this Reading test; rerunning does not delete unrelated tests. */
class IeltsTeaTransportInnovationSeeder extends Seeder
{
    public const SLUG = 'academic-reading-tea-transport-innovation';

    /** Parse and validate the complete fixture before making database changes. */
    public function definition(): array
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/ielts-reading-tea-transport-innovation.json')), true, 512, JSON_THROW_ON_ERROR);
        if (($data['schema_version'] ?? null) !== 1 || ($data['slug'] ?? null) !== self::SLUG
            || count($data['passages'] ?? []) !== 3 || ($data['total_questions'] ?? null) !== 40) {
            throw new RuntimeException('Invalid complete Reading fixture.');
        }

        $groups = [];
        foreach ($data['passages'] as $passageIndex => $passage) {
            if (empty($passage['paragraphs'])) {
                throw new RuntimeException('A passage is missing its text.');
            }
            $html = '<h2>'.e($passage['title']).'</h2><p><em>'.e($passage['introduction']).'</em></p>';
            foreach ($passage['paragraphs'] as $paragraph) {
                $label = filled($paragraph['label']) ? '<strong>'.e($paragraph['label']).'</strong> ' : '';
                $html .= '<p>'.$label.e($paragraph['text']).'</p>';
            }
            foreach ($passage['groups'] as $index => $source) {
                $questions = [];
                foreach ($source['questions'] as $question) {
                    $prepared = IeltsAuthoringService::prepareQuestion([
                        'question_number' => $question['number'],
                        'order' => count($questions) + 1,
                        'prompt' => $question['prompt'],
                        'correct_answer' => $question['answer'],
                        'options' => $question['options'] ?? [],
                        'points' => 1,
                        'word_limit' => null,
                        'explanation' => null,
                        'quote_reference' => null,
                        'drop_x' => null,
                        'drop_y' => null,
                    ], $source['question_type']);
                    $fixed = IeltsAuthoringService::fixedAnswers($source['question_type']);
                    if ($fixed && ! array_key_exists($question['answer'], $fixed)) {
                        throw new RuntimeException('Invalid fixed answer for question '.$question['number']);
                    }
                    $questions[] = $prepared;
                }
                $groups[] = [
                    'title' => 'Passage '.($passageIndex + 1).' — '.$source['title'],
                    'order' => count($groups) + 1,
                    'question_type' => $source['question_type'],
                    'response_mode' => $source['response_mode'],
                    'option_usage' => $source['option_usage'],
                    'instruction' => $source['instruction'],
                    'passage_content' => $index === 0 ? $html : null,
                    'question_content' => null,
                    'image_url' => null,
                    'audio_url' => null,
                    'transcript' => null,
                    'settings' => [
                        'source' => self::SLUG,
                        'passage_number' => $passageIndex + 1,
                        'source_question_type' => $source['source_question_type'] ?? $source['question_type'],
                    ],
                    'answerOptions' => array_map(fn ($option, $i) => [
                        'option_key' => $option['key'], 'label' => $option['text'], 'order' => $i + 1,
                    ], $source['answer_options'] ?? [], array_keys($source['answer_options'] ?? [])),
                    'questions' => $questions,
                ];
            }
        }
        $numbers = array_merge(...array_map(fn ($group) => array_column($group['questions'], 'question_number'), $groups));
        if ($numbers !== range(1, 40) || count($groups) !== 7) {
            throw new RuntimeException('Expected seven groups containing questions 1–40 exactly once.');
        }
        IeltsAuthoringService::validateGroups($groups, 'reading', 'questionGroups');

        return ['test' => $data, 'groups' => $groups];
    }

    public function run(): void
    {
        $definition = $this->definition();
        DB::transaction(function () use ($definition): void {
            $data = $definition['test'];
            $test = IeltsTest::updateOrCreate(['slug' => self::SLUG], [
                'title' => $data['title'],
                'type' => 'academic',
                'description' => 'Complete Reading: Tea and the Industrial Revolution; European Transport Systems 1990–2010; The psychology of innovation. 3 passages, 40 questions, 60 minutes.',
                'duration_minutes' => $data['duration_minutes'],
                'is_published' => true,
                'total_questions' => 40,
            ]);
            $section = $test->sections()->where('ielts_sections.skill', 'reading')->first();
            $attributes = [
                'title' => $data['title'], 'skill' => 'reading', 'test_type' => 'academic',
                'time_limit_minutes' => $data['duration_minutes'], 'total_questions' => 40,
                'description' => 'Read the three passages and answer questions 1–40. You have 60 minutes.',
                'is_active' => true,
            ];
            if ($section) {
                $section->update($attributes);
            } else {
                $section = IeltsSection::create($attributes);
            }
            $test->sections()->syncWithoutDetaching([$section->id => ['order' => 1]]);

            $groupIds = [];
            foreach ($definition['groups'] as $groupData) {
                $questions = $groupData['questions'];
                $bank = $groupData['answerOptions'];
                unset($groupData['questions'], $groupData['answerOptions']);
                $group = $section->questionGroups()->updateOrCreate(['order' => $groupData['order']], $groupData);
                $groupIds[] = $group->id;
                $keys = [];
                foreach ($bank as $option) {
                    $group->answerOptions()->updateOrCreate(['option_key' => $option['option_key']], $option);
                    $keys[] = $option['option_key'];
                }
                $group->answerOptions()->whereNotIn('option_key', $keys)->delete();
                $questionIds = [];
                foreach ($questions as $question) {
                    $record = $group->questions()->updateOrCreate(['question_number' => $question['question_number']], $question);
                    $questionIds[] = $record->id;
                }
                $group->questions()->whereNotIn('id', $questionIds)->delete();
            }
            $section->questionGroups()->whereNotIn('id', $groupIds)->delete();
            IeltsAuthoringService::syncSectionTotals($section);
            $this->ensureAcademicReadingBands();
        });
        $this->command?->info('Imported '.self::SLUG.': 3 passages, 7 groups, 40 questions, 60 minutes.');
    }

    private function ensureAcademicReadingBands(): void
    {
        // Same default Academic Reading scale as IeltsSeeder. Preserve existing configured bands.
        $bands = [
            0 => 0.0, 1 => 1.0, 2 => 1.0, 3 => 2.0, 4 => 2.0, 5 => 2.5, 6 => 2.5,
            7 => 3.0, 8 => 3.0, 9 => 3.5, 10 => 3.5, 11 => 4.0, 12 => 4.0,
            13 => 4.0, 14 => 4.5, 15 => 4.5, 16 => 5.0, 17 => 5.0, 18 => 5.0,
            19 => 5.5, 20 => 5.5, 21 => 5.5, 22 => 5.5, 23 => 6.0, 24 => 6.0,
            25 => 6.0, 26 => 6.0, 27 => 6.5, 28 => 6.5, 29 => 6.5, 30 => 7.0,
            31 => 7.0, 32 => 7.0, 33 => 7.5, 34 => 7.5, 35 => 8.0, 36 => 8.0,
            37 => 8.5, 38 => 8.5, 39 => 9.0, 40 => 9.0,
        ];
        foreach ($bands as $raw => $band) {
            IeltsBandScore::firstOrCreate(['skill' => 'reading', 'test_type' => 'academic', 'raw_score' => $raw], ['band_score' => $band]);
        }
    }
}
