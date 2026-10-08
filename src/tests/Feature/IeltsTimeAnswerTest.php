<?php

namespace Tests\Feature;

use App\Models\IeltsQuestion;
use App\Models\IeltsQuestionGroup;
use App\Models\IeltsSection;
use App\Services\IeltsWordLimitService;
use Tests\TestCase;

class IeltsTimeAnswerTest extends TestCase
{
    public function test_time_with_meridiem_is_one_number_and_one_word(): void
    {
        $section = new IeltsSection(['skill' => 'listening']);
        $group = new IeltsQuestionGroup([
            'question_type' => 'fill_in_blanks',
            'response_mode' => 'standard',
            'instruction' => 'Write ONE WORD AND/OR A NUMBER for each answer.',
        ]);
        $group->setRelation('section', $section);
        $question = new IeltsQuestion(['options' => []]);
        $question->setRelation('questionGroup', $group);

        $this->assertSame(['mode' => 'words_and_number', 'limit' => 1], IeltsWordLimitService::rule($question));
        $this->assertTrue(IeltsWordLimitService::isWithinLimit($question, '3:40 pm'));
        $this->assertTrue(IeltsWordLimitService::isWithinLimit($question, '3:40 p.m.'));
        $this->assertFalse(IeltsWordLimitService::isWithinLimit($question, '3:40 4:20 pm'));

        $group->instruction = 'Write ONE WORD ONLY for each answer.';
        $this->assertFalse(IeltsWordLimitService::isWithinLimit($question, '3:40 pm'));
    }
}
