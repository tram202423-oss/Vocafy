<?php

namespace Tests\Feature;

use App\Filament\Resources\IeltsSectionResource\Pages\EditIeltsSection;
use App\Models\IeltsSection;
use App\Models\User;
use App\Services\IeltsAuthoringService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IeltsMapAuthoringTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        // Docker's application environment points at MySQL. Isolate these saves.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_map_positions_survive_the_first_save_a_second_save_and_reopening(): void
    {
        $section = IeltsSection::create([
            'title' => 'Map authoring', 'skill' => 'reading', 'test_type' => 'academic',
            'time_limit_minutes' => 60, 'is_active' => true,
        ]);
        $group = $section->questionGroups()->create([
            'title' => 'Questions 1–2', 'question_type' => 'map_labeling',
            'response_mode' => 'drag_drop', 'option_usage' => 'once',
            'image_url' => '/map.png', 'passage_content' => '<p>Map passage.</p>',
        ]);
        foreach (['A', 'B'] as $index => $answer) {
            $group->answerOptions()->create(['option_key' => $answer, 'label' => 'Location '.$answer, 'order' => $index + 1]);
            $group->questions()->create([
                'question_number' => $index + 1, 'prompt' => 'Location '.($index + 1),
                'correct_answer' => $answer, 'options' => [],
            ]);
        }
        $questions = $group->questions()->get();
        $component = Livewire::test(EditIeltsSection::class, ['record' => $section->id]);
        $updates = [];
        foreach ($questions as $index => $question) {
            $path = "data.questionGroups.record-{$group->id}.questions.record-{$question->id}";
            $updates["{$path}.drop_x"] = 20.25 + $index * 30;
            $updates["{$path}.drop_y"] = 40.75 + $index * 20;
        }

        // Submit the deferred picker changes in the same Livewire request as Save.
        $component->update([['method' => 'save', 'params' => []]], $updates)->assertHasNoErrors();
        $this->assertSame('20.25', $questions[0]->fresh()->drop_x);
        $this->assertSame('40.75', $questions[0]->fresh()->drop_y);
        $this->assertSame('50.25', $questions[1]->fresh()->drop_x);
        $this->assertSame('60.75', $questions[1]->fresh()->drop_y);

        $component->call('save')->assertHasNoErrors();
        $reopened = Livewire::test(EditIeltsSection::class, ['record' => $section->id]);
        foreach ($updates as $path => $value) {
            $reopened->assertSet($path, number_format($value, 2, '.', ''));
        }
        $this->assertSame(['A', 'B'], $group->questions()->pluck('correct_answer')->all());
    }

    public function test_new_map_group_and_questions_save_together_on_an_existing_section(): void
    {
        $section = IeltsSection::create([
            'title' => 'New map group', 'skill' => 'listening', 'test_type' => 'academic',
            'time_limit_minutes' => 30, 'is_active' => true,
        ]);
        $component = Livewire::test(EditIeltsSection::class, ['record' => $section->id]);
        $generated = IeltsAuthoringService::appendQuestions([], [5, 6]);
        foreach ($generated as $question) {
            foreach (['answer_choice', 'answer_text', 'drop_x', 'drop_y', 'quote_reference', 'explanation', 'word_limit_mode', 'word_limit'] as $field) {
                $this->assertArrayHasKey($field, $question);
                $this->assertNull($question[$field]);
            }
        }
        foreach ($generated as &$question) {
            $question['prompt'] = 'List of places';
            $question['answer_choice'] = (string) $question['question_number'];
            $question['drop_x'] = $question['question_number'] === 5 ? 40.34 : 21.02;
            $question['drop_y'] = $question['question_number'] === 5 ? 30.36 : 12.75;
        }
        unset($question);
        $group = [
            'title' => 'Questions 5–6', 'question_type' => 'map_labeling',
            'response_mode' => 'drag_drop', 'option_usage' => 'once',
            'image_url' => '/map.png', 'settings' => ['multi_select' => false],
            'answerOptions' => [
                'bank-five' => ['option_key' => '5', 'label' => 'Desk'],
                'bank-six' => ['option_key' => '6', 'label' => 'Returns'],
            ],
            'questions' => $generated,
        ];

        $component->update([['method' => 'save', 'params' => []]], ['data.questionGroups.new-map' => $group])
            ->assertHasNoErrors();
        $this->assertSame(1, $section->questionGroups()->count());
        $this->assertSame(2, $section->questions()->count());
        $this->assertSame('40.34', $section->questions()->first()->drop_x);
        $this->assertSame(['5', '6'], $section->questions()->pluck('correct_answer')->all());
        $component->call('save')->assertHasNoErrors();
        $this->assertSame(2, $section->questions()->count());
        $this->assertSame('40.34', $section->questions()->first()->drop_x);
    }
}
