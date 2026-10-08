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
            'question_content' => '<p>Label the locations on the map.</p>',
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

    public function test_legacy_map_with_instruction_but_no_question_content_can_update_positions(): void
    {
        $section = IeltsSection::create([
            'title' => 'Legacy map', 'skill' => 'reading', 'test_type' => 'academic',
            'time_limit_minutes' => 60, 'is_active' => true,
        ]);
        $group = $section->questionGroups()->create([
            'title' => 'Questions 5–6', 'question_type' => 'map_labeling',
            'response_mode' => 'drag_drop', 'option_usage' => 'once',
            'image_url' => '/map.png', 'passage_content' => '<p>Reading passage for the map.</p>',
            'instruction' => 'Choose the correct letter for each place on the map.',
            'question_content' => null,
        ]);
        foreach (['A', 'B'] as $index => $answer) {
            $group->answerOptions()->create([
                'option_key' => $answer, 'label' => 'Location '.$answer, 'order' => $index + 1,
            ]);
            $group->questions()->create([
                'question_number' => $index + 5, 'prompt' => '[blank_N]',
                'correct_answer' => $answer, 'options' => [],
                'drop_x' => 20 + $index * 30, 'drop_y' => 25 + $index * 30,
            ]);
        }

        $question = $group->questions()->firstOrFail();
        $path = "data.questionGroups.record-{$group->id}.questions.record-{$question->id}";
        Livewire::test(EditIeltsSection::class, ['record' => $section->id])
            ->update([['method' => 'save', 'params' => []]], [
                "{$path}.drop_x" => 37.25,
                "{$path}.drop_y" => 62.75,
            ])
            ->assertHasNoErrors();

        $this->assertSame('37.25', $question->fresh()->drop_x);
        $this->assertSame('62.75', $question->fresh()->drop_y);
    }

    public function test_editing_another_group_keeps_existing_map_positions(): void
    {
        $section = IeltsSection::create([
            'title' => 'Reading with map', 'skill' => 'reading', 'test_type' => 'academic',
            'time_limit_minutes' => 60, 'is_active' => true,
        ]);
        $other = $section->questionGroups()->create([
            'title' => 'Questions 1–4', 'question_type' => 'short_answer',
            'response_mode' => 'standard', 'passage_content' => '<p>Passage.</p>',
        ]);
        $otherQuestion = $other->questions()->create([
            'question_number' => 1, 'prompt' => 'First question',
            'correct_answer' => 'First', 'options' => [],
        ]);
        $map = $section->questionGroups()->create([
            'title' => 'Questions 5–6', 'question_type' => 'map_labeling',
            'response_mode' => 'drag_drop', 'option_usage' => 'once',
            'image_url' => '/map.png',
            'instruction' => 'Choose labels for the map.',
        ]);
        foreach (['A', 'B'] as $index => $answer) {
            $map->answerOptions()->create([
                'option_key' => $answer, 'label' => 'Place '.$answer, 'order' => $index + 1,
            ]);
            $map->questions()->create([
                'question_number' => 5 + $index, 'prompt' => '[blank_N]',
                'correct_answer' => $answer, 'options' => [],
                'drop_x' => 20 + $index * 30, 'drop_y' => 25 + $index * 30,
            ]);
        }

        $component = Livewire::test(EditIeltsSection::class, ['record' => $section->id]);
        $component->set(
            "data.questionGroups.record-{$other->id}.questions.record-{$otherQuestion->id}.prompt",
            'Updated first question'
        );
        foreach ($map->questions as $question) {
            $path = "data.questionGroups.record-{$map->id}.questions.record-{$question->id}";
            $component->assertSet("{$path}.drop_x", $question->drop_x);
            $component->assertSet("{$path}.drop_y", $question->drop_y);
        }
        $component->call('save')->assertHasNoErrors();

        $this->assertSame('Updated first question', $otherQuestion->fresh()->prompt);
        $this->assertSame(['20.00', '50.00'], $map->questions()->pluck('drop_x')->all());
        $this->assertSame(['25.00', '55.00'], $map->questions()->pluck('drop_y')->all());

        // A stale browser can send empty coordinates for an unchanged map row
        // while the admin edits the title of another group.
        $firstMapQuestion = $map->questions()->firstOrFail();
        $mapPath = "data.questionGroups.record-{$map->id}.questions.record-{$firstMapQuestion->id}";
        $component = Livewire::test(EditIeltsSection::class, ['record' => $section->id]);
        $component->set("data.questionGroups.record-{$other->id}.title", 'Questions 1–5')
            ->set("{$mapPath}.drop_x", null)
            ->set("{$mapPath}.drop_y", null)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('20.00', $firstMapQuestion->fresh()->drop_x);
        $this->assertSame('25.00', $firstMapQuestion->fresh()->drop_y);

        // An explicit clear still requires a new position before saving a drag map.
        Livewire::test(EditIeltsSection::class, ['record' => $section->id])
            ->set("{$mapPath}.drop_x", null)
            ->set("{$mapPath}.drop_y", null)
            ->set("{$mapPath}.map_position_cleared", true)
            ->call('save')
            ->assertHasErrors(["data.questionGroups.record-{$map->id}.map_position_picker"]);
        $this->assertSame('20.00', $firstMapQuestion->fresh()->drop_x);

        // Filament may temporarily hide nested inputs while another group is edited.
        // Coordinate fields must still dehydrate into the relationship payload.
        $checked = 0;
        foreach ($component->instance()->form->getFlatComponents(withHidden: true) as $field) {
            $path = $field->getStatePath();
            if (str_contains($path, "record-{$map->id}.questions.")
                && (str_ends_with($path, '.drop_x') || str_ends_with($path, '.drop_y'))) {
                $field->hidden();
                $this->assertTrue($field->isDehydrated(), $path.' was excluded from save');
                $checked++;
            }
        }
        $this->assertSame(4, $checked);
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
            $question['prompt'] = '';
            $question['answer_choice'] = (string) $question['question_number'];
            $question['drop_x'] = $question['question_number'] === 5 ? 40.34 : 21.02;
            $question['drop_y'] = $question['question_number'] === 5 ? 30.36 : 12.75;
        }
        unset($question);
        $group = [
            'title' => 'Questions 5–6', 'question_type' => 'map_labeling',
            'response_mode' => 'drag_drop', 'option_usage' => 'once',
            'image_url' => '/map.png', 'question_content' => '<p>Label the shared map.</p>',
            'settings' => ['multi_select' => false],
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
        $this->assertSame(['', ''], $section->questions()->pluck('prompt')->all());
        $component->call('save')->assertHasNoErrors();
        $this->assertSame(2, $section->questions()->count());
        $this->assertSame('40.34', $section->questions()->first()->drop_x);
    }

    public function test_standard_map_uses_shared_content_and_result_shows_correct_position(): void
    {
        $section = IeltsSection::create([
            'title' => 'Standard map', 'skill' => 'listening', 'test_type' => 'academic',
            'time_limit_minutes' => 30, 'is_active' => true,
        ]);
        $question = IeltsAuthoringService::appendQuestions([], [1]);
        foreach ($question as &$entry) {
            $entry['prompt'] = '';
            $entry['answer_choice'] = 'A';
            $entry['options'] = [
                'option-a' => ['key' => 'A', 'text' => 'Library'],
                'option-b' => ['key' => 'B', 'text' => 'Cafe'],
            ];
            $entry['drop_x'] = 35.5;
            $entry['drop_y'] = 42.25;
        }
        unset($entry);

        Livewire::test(EditIeltsSection::class, ['record' => $section->id])
            ->update([['method' => 'save', 'params' => []]], ['data.questionGroups.new-map' => [
                'title' => 'Map questions', 'question_type' => 'map_labeling',
                'response_mode' => 'standard', 'option_usage' => 'repeat', 'image_url' => '/map.png',
                'question_content' => '<p>Label the building on the map.</p>',
                'settings' => ['map_answer_mode' => 'choices', 'multi_select' => false],
                'questions' => $question,
            ]])
            ->assertHasNoErrors();

        $group = $section->questionGroups()->with(['questions', 'answerOptions'])->firstOrFail();
        $this->assertSame('A', $group->questions->first()->correct_answer);
        $this->assertSame('', $group->questions->first()->prompt);

        $reopened = Livewire::test(EditIeltsSection::class, ['record' => $section->id]);
        $reopened->assertSet(
            "data.questionGroups.record-{$group->id}.questions.record-{$group->questions->first()->id}.answer_choice",
            'A'
        );

        $html = view('ielts.partials.map-result', ['group' => $group])->render();
        $this->assertStringContainsString('Label the building on the map.', $html);
        $this->assertStringContainsString('left: 35.5%; top: 42.25%;', $html);
        $this->assertStringContainsString('Library', $html);
    }

}
