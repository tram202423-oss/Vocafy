<?php

namespace Tests\Feature;

use App\Models\IeltsQuestion;
use App\Models\IeltsSection;
use App\Models\IeltsSubmission;
use App\Models\IeltsTest;
use App\Models\IeltsUserAnswer;
use App\Models\User;
use App\Services\IeltsScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IeltsReadingListeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected IeltsTest $test;
    protected IeltsScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\IeltsSeeder::class);
        $this->user = User::factory()->create();
        $this->test = IeltsTest::where('slug', 'cambridge-ielts-18-academic-test-1')->firstOrFail();
        $this->scoringService = app(IeltsScoringService::class);
    }

    /**
     * Test Answer Matching Engine in IeltsScoringService
     */
    public function test_answer_matching_with_various_formats(): void
    {
        // 1. Shorthand True/False/Not Given
        $this->assertTrue($this->scoringService->checkAnswer('TRUE', 'TRUE'));
        $this->assertTrue($this->scoringService->checkAnswer('true', 'TRUE'));
        $this->assertTrue($this->scoringService->checkAnswer('t', 'TRUE'));
        $this->assertTrue($this->scoringService->checkAnswer('f', 'FALSE'));
        $this->assertTrue($this->scoringService->checkAnswer('ng', 'NOT GIVEN'));
        $this->assertFalse($this->scoringService->checkAnswer('f', 'TRUE'));

        // 2. Multi-options split by slash or pipe
        $this->assertTrue($this->scoringService->checkAnswer('3', '3 / three'));
        $this->assertTrue($this->scoringService->checkAnswer('three', '3 / three'));
        $this->assertTrue($this->scoringService->checkAnswer('Three', '3 / three'));
        $this->assertTrue($this->scoringService->checkAnswer('50', '50 / fifty'));
        $this->assertTrue($this->scoringService->checkAnswer('fifty', '50 / fifty'));

        // 3. Currency symbol stripping
        $this->assertTrue($this->scoringService->checkAnswer('£50', '50 / fifty'));
        $this->assertTrue($this->scoringService->checkAnswer('$50', '50 / fifty'));

        // 4. Hyphens vs Spaces vs Combined
        $this->assertTrue($this->scoringService->checkAnswer('multi-storey', 'multi-storey'));
        $this->assertTrue($this->scoringService->checkAnswer('multi storey', 'multi-storey'));
        $this->assertTrue($this->scoringService->checkAnswer('multistorey', 'multi-storey'));
        $this->assertTrue($this->scoringService->checkAnswer('clear cutting', 'clear-cutting'));

        // 5. Phone numbers with or without spaces
        $this->assertTrue($this->scoringService->checkAnswer('07700 900342', '07700 900342'));
        $this->assertTrue($this->scoringService->checkAnswer('07700900342', '07700 900342'));

        // 6. Trailing punctuation
        $this->assertTrue($this->scoringService->checkAnswer('urban.', 'urban'));
        $this->assertTrue($this->scoringService->checkAnswer('mismanagement,', 'mismanagement'));
    }

    /**
     * Test Academic Reading Test Flow (Room, Save, Submit, Scoring, Result)
     */
    public function test_reading_exam_flow(): void
    {
        $readingSection = $this->test->sections()->where('ielts_sections.skill', 'reading')->firstOrFail();
        $this->assertEquals(40, $readingSection->questions()->count());

        // 1. Candidate starts reading test
        $response = $this->actingAs($this->user)->post(route('ielts.tests.start', [
            'slug' => $this->test->slug,
            'section_id' => $readingSection->id,
        ]));
        $response->assertRedirect();

        $submission = IeltsSubmission::where('user_id', $this->user->id)
            ->where('ielts_section_id', $readingSection->id)
            ->latest()
            ->firstOrFail();

        $this->assertEquals('in_progress', $submission->status->value);
        $this->assertEquals(40, $submission->userAnswers()->count());

        // 2. Candidate loads exam room
        $roomResponse = $this->actingAs($this->user)->get(route('ielts.exam.room', $submission->id));
        $roomResponse->assertStatus(200);
        $roomResponse->assertSee('Reading');

        // 3. Candidate answers first question via auto-save
        $firstQ = $readingSection->questions()->first();
        $saveResponse = $this->actingAs($this->user)->postJson(route('ielts.exam.save', $submission->id), [
            'question_id' => $firstQ->id,
            'answer' => $firstQ->correct_answer,
            'is_flagged' => true,
        ]);
        $saveResponse->assertStatus(200);

        $savedAnswer = IeltsUserAnswer::where('ielts_submission_id', $submission->id)
            ->where('ielts_question_id', $firstQ->id)
            ->first();
        $this->assertEquals($firstQ->correct_answer, $savedAnswer->user_answer);
        $this->assertTrue((bool)$savedAnswer->is_flagged_for_review);

        // 4. Candidate answers 30 questions correctly and submits
        $answersPayload = [];
        foreach ($readingSection->questions as $idx => $q) {
            if ($idx < 30) {
                $answersPayload[$q->id] = $q->correct_answer;
            } else {
                $answersPayload[$q->id] = 'wrong';
            }
        }

        $submitResponse = $this->actingAs($this->user)->postJson(route('ielts.exam.submit', $submission->id), [
            'answers' => $answersPayload,
        ]);
        $submitResponse->assertStatus(200);
        $submitResponse->assertJson(['status' => 'success']);

        $submission->refresh();
        $this->assertEquals('completed', $submission->status->value);
        $this->assertEquals(30, $submission->raw_score);
        // Academic reading raw 30 = Band 7.0
        $this->assertEquals(7.0, (float)$submission->band_score);

        // 5. Candidate views result page
        $resultResponse = $this->actingAs($this->user)->get(route('ielts.exam.result', $submission->id));
        $resultResponse->assertStatus(200);
        $resultResponse->assertSee('7.0');
        $resultResponse->assertSee('30');
    }

    /**
     * Test Academic & General Listening Test Flow (Room, Save, Submit, Scoring, Result)
     */
    public function test_listening_exam_flow(): void
    {
        $listeningSection = $this->test->sections()->where('ielts_sections.skill', 'listening')->firstOrFail();
        $this->assertEquals(40, $listeningSection->questions()->count());

        // 1. Candidate starts listening test
        $response = $this->actingAs($this->user)->post(route('ielts.tests.start', [
            'slug' => $this->test->slug,
            'section_id' => $listeningSection->id,
        ]));
        $response->assertRedirect();

        $submission = IeltsSubmission::where('user_id', $this->user->id)
            ->where('ielts_section_id', $listeningSection->id)
            ->latest()
            ->firstOrFail();

        $this->assertEquals('in_progress', $submission->status->value);
        $this->assertEquals(40, $submission->userAnswers()->count());

        // 2. Candidate loads exam room
        $roomResponse = $this->actingAs($this->user)->get(route('ielts.exam.room', $submission->id));
        $roomResponse->assertStatus(200);
        $roomResponse->assertSee('Listening');

        // 3. Candidate answers 35 questions correctly and submits
        $answersPayload = [];
        foreach ($listeningSection->questions as $idx => $q) {
            if ($idx < 35) {
                // Test variation: if prompt has 3 / three, send 'three'
                if ($q->correct_answer === '3 / three') {
                    $answersPayload[$q->id] = 'three';
                } elseif ($q->correct_answer === '50 / fifty') {
                    $answersPayload[$q->id] = '£50';
                } else {
                    $answersPayload[$q->id] = $q->correct_answer;
                }
            } else {
                $answersPayload[$q->id] = 'incorrect';
            }
        }

        $submitResponse = $this->actingAs($this->user)->postJson(route('ielts.exam.submit', $submission->id), [
            'answers' => $answersPayload,
        ]);
        $submitResponse->assertStatus(200);
        $submitResponse->assertJson(['status' => 'success']);

        $submission->refresh();
        $this->assertEquals('completed', $submission->status->value);
        $this->assertEquals(35, $submission->raw_score);
        // Listening raw 35 = Band 8.0
        $this->assertEquals(8.0, (float)$submission->band_score);

        // 4. Candidate views result page
        $resultResponse = $this->actingAs($this->user)->get(route('ielts.exam.result', $submission->id));
        $resultResponse->assertStatus(200);
        $resultResponse->assertSee('8.0');
        $resultResponse->assertSee('35');
        $resultResponse->assertSee('Audio Transcript');
    }
}
