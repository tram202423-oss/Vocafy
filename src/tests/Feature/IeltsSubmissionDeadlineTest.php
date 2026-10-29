<?php

namespace Tests\Feature;

use App\Models\IeltsSubmission;
use App\Models\IeltsTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IeltsSubmissionDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function startReading(): array
    {
        $this->seed(\Database\Seeders\IeltsReading02Seeder::class);
        $user = User::factory()->create();
        $test = IeltsTest::where('slug', 'ielts-reading-02')->firstOrFail();
        $section = $test->sections()->where('ielts_sections.skill', 'reading')->firstOrFail();

        $this->actingAs($user)->post(route('ielts.tests.start', [
            'slug' => $test->slug,
            'section_id' => $section->id,
        ]))->assertRedirect();

        $submission = IeltsSubmission::where('user_id', $user->id)->firstOrFail();
        $metadata = $submission->metadata;
        $deadline = now()->addSeconds(2)->startOfSecond();
        $metadata['deadline_at'] = $deadline->toIso8601String();
        $submission->update(['metadata' => $metadata]);

        return [$submission, $section->questions()->firstOrFail(), $deadline];
    }

    public function test_final_answers_arriving_just_after_deadline_are_scored_but_autosave_is_closed(): void
    {
        [$submission, $question, $deadline] = $this->startReading();
        $this->travelTo($deadline->copy()->addSecond());

        $this->postJson(route('ielts.exam.save', $submission->id), [
            'question_id' => $question->id,
            'answer' => $question->correct_answer,
        ])->assertStatus(409);

        $this->artisan('ielts:complete-expired')->assertExitCode(0);
        $this->assertSame('in_progress', $submission->fresh()->status->value);

        $this->postJson(route('ielts.exam.submit', $submission->id), [
            'answers' => [$question->id => $question->correct_answer],
            'expected_revision' => 0,
        ])->assertOk();

        $this->assertSame('completed', $submission->fresh()->status->value);
        $this->assertSame($question->correct_answer, $submission->userAnswers()->where('ielts_question_id', $question->id)->firstOrFail()->user_answer);
    }

    public function test_final_answers_after_grace_window_are_not_applied(): void
    {
        [$submission, $question, $deadline] = $this->startReading();
        $this->travelTo($deadline->copy()->addSeconds(IeltsSubmission::FINAL_SUBMISSION_GRACE_SECONDS + 1));

        $this->postJson(route('ielts.exam.submit', $submission->id), [
            'answers' => [$question->id => $question->correct_answer],
        ])->assertOk();

        $this->assertNull($submission->userAnswers()->where('ielts_question_id', $question->id)->firstOrFail()->user_answer);
    }
}
