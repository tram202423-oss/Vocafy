<?php

namespace Database\Seeders;

use App\Models\IeltsBandScore;
use App\Models\IeltsSection;
use App\Models\IeltsTest;
use App\Services\IeltsAuthoringService;
use App\Services\IeltsMultiSelectService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IeltsCallUnlimitedListeningSeeder extends Seeder
{
    public const SLUG = 'listening-call-unlimited-london-eye';

    public function definition(): array
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/ielts-listening-call-unlimited.json')), true, 512, JSON_THROW_ON_ERROR);
        $groups = [];
        foreach ($data['groups'] as $index => $source) {
            $settings = ['source' => self::SLUG, 'part_number' => $source['part']];
            if ($source['multi_select'] ?? false) {
                $settings += [
                    'multi_select' => true, 'start_number' => $source['start'],
                    'prompt' => $source['prompt'], 'options' => $source['options'],
                    'correct_keys' => $source['correct_keys'], 'selection_limit' => count($source['correct_keys']),
                ];
            }
            $group = [
                'title' => 'Part '.$source['part'].' — '.$source['title'],
                'order' => $index + 1, 'question_type' => $source['question_type'],
                'response_mode' => 'standard', 'option_usage' => 'repeat',
                'instruction' => $source['instruction'], 'question_content' => $source['question_content'],
                'passage_content' => null, 'settings' => $settings,
                // Audio/transcript are deliberately omitted: a rerun preserves later uploads.
                'questions' => array_map(fn ($q, $i) => [
                    'question_number' => $q['number'], 'order' => $i + 1,
                    'prompt' => $q['prompt'], 'correct_answer' => $q['answer'],
                    'options' => $q['options'] ?? null, 'word_limit' => $source['word_limit'] ?? null,
                    'points' => 1,
                ], $source['questions'], array_keys($source['questions'])),
            ];
            if ($source['multi_select'] ?? false) {
                $group['questions'] = IeltsMultiSelectService::questions($group);
            }
            if ($group['question_content']) {
                preg_match_all('/\[blank_(\d+)\]/', $group['question_content'], $matches);
                if (array_map('intval', $matches[1]) !== array_column($group['questions'], 'question_number')) {
                    throw new RuntimeException('Completion gaps do not match the question numbers.');
                }
            }
            $groups[] = $group;
        }
        $numbers = array_merge(...array_map(fn ($g) => array_column($g['questions'], 'question_number'), $groups));
        if ($data['slug'] !== self::SLUG || count($groups) !== 9 || $numbers !== range(1, 40)) {
            throw new RuntimeException('Expected nine groups and questions 1–40.');
        }
        IeltsAuthoringService::validateGroups($groups, 'listening', 'questionGroups');
        return ['test' => $data, 'groups' => $groups];
    }

    public function run(): void
    {
        $definition = $this->definition();
        DB::transaction(function () use ($definition): void {
            $data = $definition['test'];
            $test = IeltsTest::updateOrCreate(['slug' => self::SLUG], [
                'title' => $data['title'], 'type' => 'academic',
                'description' => '4 Listening Parts: Call Unlimited Service; Job descriptions and Recruitment Process; Details of studying online; The London Eye. 40 questions.',
                'duration_minutes' => $data['duration_minutes'], 'total_questions' => 40, 'is_published' => true,
            ]);
            $section = $test->sections()->where('ielts_sections.skill', 'listening')->first();
            $attributes = [
                'title' => $data['title'], 'skill' => 'listening', 'test_type' => 'academic',
                'time_limit_minutes' => $data['duration_minutes'], 'total_questions' => 40,
                'description' => 'Listen and answer questions 1–40. Follow the word limit given for each group.',
                'is_active' => true,
            ];
            if ($section) {
                $section->update($attributes);
            } else {
                $section = IeltsSection::create($attributes);
            }
            $test->sections()->syncWithoutDetaching([$section->id => ['order' => 1]]);
            foreach ($definition['groups'] as $groupData) {
                $questions = $groupData['questions'];
                unset($groupData['questions']);
                $group = $section->questionGroups()->updateOrCreate(['order' => $groupData['order']], $groupData);
                foreach ($questions as $question) {
                    $group->questions()->updateOrCreate(['question_number' => $question['question_number']], $question);
                }
            }
            IeltsAuthoringService::syncSectionTotals($section);
            // Use the app's existing Listening scale; preserve configured bands.
            $bands = [0,1,2,2,2.5,2.5,3,3,3.5,3.5,4,4,4,4.5,4.5,4.5,5,5,5.5,5.5,5.5,5.5,5.5,6,6,6,6.5,6.5,6.5,6.5,7,7,7.5,7.5,7.5,8,8,8.5,8.5,9,9];
            foreach ($bands as $raw => $band) {
                IeltsBandScore::firstOrCreate(['skill' => 'listening', 'test_type' => 'academic', 'raw_score' => $raw], ['band_score' => $band]);
            }
            $this->command?->info('Imported test '.$test->id.', section '.$section->id.': 4 Parts, 9 groups, 40 questions.');
        });
    }
}
