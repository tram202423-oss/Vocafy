<?php

namespace App\Services;

use App\Models\IeltsBandScore;
use App\Models\IeltsQuestion;
use App\Models\IeltsSubmission;
use Illuminate\Support\Facades\Log;

class IeltsScoringService
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Chấm điểm và tính band score cho một bài nộp IELTS (Reading, Listening, Writing)
     */
    public function scoreSubmission(IeltsSubmission $submission): IeltsSubmission
    {
        $skill = $submission->skill ?? 'reading';

        if ($skill === 'writing') {
            return $this->scoreWritingSubmission($submission);
        }

        // Chấm điểm cho Reading và Listening
        $rawScore = 0;
        $userAnswers = $submission->userAnswers()->with('question.questionGroup')->get();
        $oneUseAnswerCounts = [];

        foreach ($userAnswers as $userAnswer) {
            $question = $userAnswer->question;
            $group = $question?->questionGroup;
            $answerKey = trim((string) $userAnswer->user_answer);

            if ($this->usesDragDrop($group)
                && ($group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat')) === 'once'
                && $answerKey !== '') {
                $normalizedKey = mb_strtolower($answerKey, 'UTF-8');
                $oneUseAnswerCounts[$group->id][$normalizedKey] = ($oneUseAnswerCounts[$group->id][$normalizedKey] ?? 0) + 1;
            }
        }

        foreach ($userAnswers as $userAnswer) {
            $question = $userAnswer->question;
            if (!$question) {
                continue;
            }

            $isCorrect = $this->checkAnswer($userAnswer->user_answer, $question->correct_answer);
            $group = $question->questionGroup;
            if ($this->usesDragDrop($group)
                && ($group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat')) === 'once') {
                $normalizedKey = mb_strtolower(trim((string) $userAnswer->user_answer), 'UTF-8');
                if ($normalizedKey !== '' && ($oneUseAnswerCounts[$group->id][$normalizedKey] ?? 0) > 1) {
                    $isCorrect = false;
                }
            }

            $userAnswer->update([
                'is_correct' => $isCorrect,
            ]);

            if ($isCorrect) {
                $rawScore += $question->points ?? 1;
            }
        }

        $testType = 'academic';
        if ($submission->test_type instanceof \App\Enums\IeltsTestTypeEnum) {
            $testType = $submission->test_type->value;
        } elseif (is_string($submission->test_type) && !empty($submission->test_type)) {
            $testType = $submission->test_type;
        } elseif (is_object($submission->test_type) && isset($submission->test_type->value)) {
            $testType = $submission->test_type->value;
        }

        // Quy đổi sang Band Score theo bảng chuẩn
        $bandScore = IeltsBandScore::convert($skill, $testType, $rawScore);

        $submission->update([
            'raw_score' => $rawScore,
            'band_score' => $bandScore,
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        return $submission->fresh();
    }

    /**
     * Chấm điểm bài Writing thông qua Gemini AI theo 4 tiêu chí chuẩn IELTS
     */
    public function scoreWritingSubmission(IeltsSubmission $submission): IeltsSubmission
    {
        $userAnswers = $submission->userAnswers()->with('question.questionGroup')->get();
        $taskScores = [];
        $evaluations = [];

        foreach ($userAnswers as $userAnswer) {
            $question = $userAnswer->question;
            $essayText = trim((string) $userAnswer->user_answer);

            if (empty($essayText)) {
                $userAnswer->update(['is_correct' => false]);
                continue;
            }

            $isTask1 = ($question->question_number === 1);
            $essayType = $isTask1 ? 'ielts_academic_task1' : 'ielts_academic_task2';
            $topic = $question->prompt ?? ($question->questionGroup?->title ?? 'IELTS Writing Prompt');

            try {
                $eval = $this->geminiService->evaluateWriting(
                    topic: $topic,
                    essay: $essayText,
                    examCategory: 'ielts_academic',
                    essayType: $essayType,
                    hasImage: false
                );

                $band = (float) ($eval['overallScore'] ?? 6.0);
                $taskScores[$question->question_number] = $band;
                $evaluations[$question->question_number] = $eval;

                $userAnswer->update([
                    'is_correct' => ($band >= 5.0),
                    'notes' => json_encode($eval, JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $e) {
                Log::error('Gemini Writing Evaluation Error: ' . $e->getMessage());
                // Fallback nếu Gemini API tạm ngắt
                $fallbackBand = $isTask1 ? 6.0 : 6.5;
                $taskScores[$question->question_number] = $fallbackBand;
            }
        }

        // Cách tính IELTS Writing Overall Band chuẩn: (Task 1 * 1/3) + (Task 2 * 2/3)
        $t1Score = $taskScores[1] ?? 5.5;
        $t2Score = $taskScores[2] ?? ($taskScores[1] ?? 5.5);
        $overallWritingBand = round((($t1Score * 1) + ($t2Score * 2)) / 3 * 2) / 2; // Làm tròn về nửa band gần nhất (.0 hoặc .5)

        $metadata = $submission->metadata ?? [];
        $metadata['writing_evaluations'] = $evaluations;

        $submission->update([
            'raw_score' => count(array_filter($taskScores)),
            'band_score' => $overallWritingBand,
            'completed_at' => now(),
            'status' => 'completed',
            'metadata' => $metadata,
        ]);

        return $submission->fresh();
    }

    /**
     * So khớp đáp án của thí sinh với đáp án chuẩn
     */
    public function checkAnswer(?string $userAnswer, ?string $correctAnswer): bool
    {
        if ($userAnswer === null || $correctAnswer === null) {
            return false;
        }

        $userStr = trim($userAnswer);
        $correctStr = trim($correctAnswer);
        if ($userStr === '' || $correctStr === '') {
            return false;
        }

        // Trường hợp correctAnswer là JSON chứa nhiều đáp án đúng (ví dụ: ["center", "the center", "centre"])
        $decoded = json_decode($correctStr, true);
        if (is_array($decoded)) {
            foreach ($decoded as $acceptable) {
                if ($this->isMatch($userStr, (string) $acceptable)) {
                    return true;
                }
            }
            return false;
        }

        // Trường hợp phân cách bằng dấu gạch chéo (/), gạch đứng (|), hoặc chấm phẩy (;)
        $options = preg_split('/[\/|;]/', $correctStr);
        foreach ($options as $opt) {
            if ($this->isMatch($userStr, trim($opt))) {
                return true;
            }
        }

        return $this->isMatch($userStr, $correctStr);
    }

    private function usesDragDrop(?\App\Models\IeltsQuestionGroup $group): bool
    {
        return $group !== null && ($group->response_mode === 'drag_drop'
            || $group->question_type === \App\Enums\IeltsQuestionTypeEnum::DRAG_DROP);
    }

    private function isMatch(string $user, string $target): bool
    {
        $cleanUser = $this->normalizeString($user);
        $cleanTarget = $this->normalizeString($target);

        if ($cleanUser === $cleanTarget) {
            return true;
        }

        // Shorthand handling: True/False/Not Given & Yes/No
        $shorthands = [
            't' => 'true',
            'f' => 'false',
            'ng' => 'not given',
            'y' => 'yes',
            'n' => 'no',
        ];
        if (isset($shorthands[$cleanUser]) && $shorthands[$cleanUser] === $cleanTarget) {
            return true;
        }
        if (isset($shorthands[$cleanTarget]) && $shorthands[$cleanTarget] === $cleanUser) {
            return true;
        }

        // Ignore hyphens and spaces differences (e.g. multi-storey vs multi storey vs multistorey)
        $noHyphenUser = str_replace(['-', ' '], '', $cleanUser);
        $noHyphenTarget = str_replace(['-', ' '], '', $cleanTarget);
        if (!empty($noHyphenUser) && $noHyphenUser === $noHyphenTarget) {
            return true;
        }

        // Phone numbers / Pure digit matching (e.g. 07700 900342 vs 07700900342)
        $digitsUser = preg_replace('/\D+/', '', $user);
        $digitsTarget = preg_replace('/\D+/', '', $target);
        if (strlen($digitsUser) >= 5 && $digitsUser === $digitsTarget) {
            return true;
        }

        return false;
    }

    private function normalizeString(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        // Chuẩn hóa dấu nháy và ký hiệu tiền tệ
        $str = str_replace(["'", '"', '’', '“', '”', '£', '$', '€', '₫'], '', $str);
        // Bỏ dấu chấm, phẩy ở đuôi nếu thí sinh gõ dấu câu kết thúc
        $str = rtrim($str, '.,:;!?');
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }
}
