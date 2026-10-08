<?php

namespace App\Services;

use App\Models\IeltsBandScore;
use App\Models\IeltsQuestion;
use App\Models\IeltsSubmission;
use App\Models\IeltsUserAnswer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        if (in_array($skill, ['writing', 'speaking'], true)) {
            if ($submission->status->value !== 'completed') {
                $submission->update([
                    'status' => 'completed', 'completed_at' => now(),
                    'duration_seconds' => $this->duration($submission),
                    'band_score' => $submission->teacher_band_score,
                ]);
                app(IeltsAiAssessmentService::class)->request($submission);
            }
            return $submission->fresh();
        }

        // Chấm điểm cho Reading và Listening
        $rawScore = 0;
        $userAnswers = $submission->userAnswers()->with('question.questionGroup.questions', 'question.questionGroup.section')->get();
        $snapshotQuestions = app(IeltsExamSnapshotService::class)->apply($submission)['questions'] ?? [];
        $questionFor = fn (IeltsUserAnswer $answer) => $snapshotQuestions[(string) ($answer->question_snapshot_id ?: $answer->ielts_question_id)]
            ?? $answer->question;
        $oneUseAnswerCounts = [];
        $multiUsedKeys = [];

        foreach ($userAnswers as $userAnswer) {
            $question = $questionFor($userAnswer);
            $group = $question?->questionGroup;
            $answerKey = trim((string) $userAnswer->user_answer);

            if (($this->usesDragDrop($group) || $group?->question_type?->value === 'matching_information')
                && ($group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat')) === 'once'
                && $answerKey !== '') {
                $normalizedKey = mb_strtolower($answerKey, 'UTF-8');
                $oneUseAnswerCounts[$group->id][$normalizedKey] = ($oneUseAnswerCounts[$group->id][$normalizedKey] ?? 0) + 1;
            }
        }

        foreach ($userAnswers as $userAnswer) {
            $question = $questionFor($userAnswer);
            if (!$question) {
                continue;
            }

            $isCorrect = $this->checkQuestionAnswer($userAnswer->user_answer, $question);
            if ($isCorrect && ! IeltsWordLimitService::isWithinLimit($question, $userAnswer->user_answer)) {
                $isCorrect = false;
            }
            $group = $question->questionGroup;
            $multi = IeltsMultiSelectService::enabled($group);
            if ($multi) {
                $key = IeltsMultiSelectService::normalize($userAnswer->user_answer);
                $isCorrect = $key !== ''
                    && in_array($key, IeltsMultiSelectService::correctKeys($group), true)
                    && ! isset($multiUsedKeys[$group->id][$key]);
                $multiUsedKeys[$group->id][$key] = true;
            }
            if (($this->usesDragDrop($group) || $group?->question_type?->value === 'matching_information')
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
                $rawScore += 1;
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
        $bandScore = (int) $submission->total_questions === 40
            ? IeltsBandScore::convert($skill, $testType, $rawScore) : null;

        $submission->update([
            'raw_score' => $rawScore,
            'band_score' => $bandScore,
            'completed_at' => now(),
            'duration_seconds' => $this->duration($submission),
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
        $snapshotQuestions = app(IeltsExamSnapshotService::class)->apply($submission)['questions'] ?? [];
        $taskScores = [];
        $evaluations = [];
        $taskStatuses = [];

        foreach ($userAnswers as $userAnswer) {
            $question = $snapshotQuestions[(string) ($userAnswer->question_snapshot_id ?: $userAnswer->ielts_question_id)]
                ?? $userAnswer->question;
            if (! $question) {
                continue;
            }

            $taskNumber = (int) $question->question_number === 1 ? 1 : 2;
            $essayText = trim((string) $userAnswer->user_answer);
            if ($essayText === '') {
                $taskStatuses[$taskNumber] = 'missing';
                $userAnswer->update(['is_correct' => false, 'notes' => null]);
                continue;
            }

            $isTask1 = $taskNumber === 1;
            $category = ($submission->test_type?->value ?? 'academic') === 'general_training' ? 'ielts_general' : 'ielts_academic';
            $essayType = $category . ($isTask1 ? '_task1' : '_task2');
            $topic = implode("\n\n", array_filter([$question->questionGroup?->title, $question->questionGroup?->instruction, strip_tags($question->questionGroup?->question_content ?? ''), $question->prompt]));

            try {
                $image = app(IeltsWritingImageService::class)->load($question->questionGroup?->image_url);
                $eval = $this->geminiService->evaluateWriting(
                    topic: $topic,
                    essay: $essayText,
                    examCategory: $category,
                    essayType: $essayType,
                    hasImage: $image !== null,
                    imageInput: $image,
                );
                $eval = IeltsAiResultValidator::validate($eval, 'writing');

                $band = $eval['overallScore'] ?? null;
                if (! is_numeric($band) || ! is_finite((float) $band) || (float) $band < 0 || (float) $band > 9) {
                    throw new \UnexpectedValueException('Writing evaluator returned an invalid overallScore.');
                }

                $band = (float) $band;
                $taskScores[$taskNumber] = $band;
                $taskStatuses[$taskNumber] = 'graded';
                $evaluations[$taskNumber] = $eval;
                $userAnswer->update([
                    'is_correct' => null,
                    'notes' => json_encode($eval, JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $e) {
                Log::error('Gemini Writing Evaluation Error: ' . $e->getMessage());
                $taskStatuses[$taskNumber] = 'failed';
                $userAnswer->update(['is_correct' => null, 'notes' => null]);
            }
        }

        foreach ([1, 2] as $taskNumber) {
            $taskStatuses[$taskNumber] ??= 'missing';
        }

        $isFullyGraded = $taskStatuses[1] === 'graded' && $taskStatuses[2] === 'graded';
        $overallWritingBand = null;
        if ($isFullyGraded) {
            // IELTS Writing weighs Task 2 twice as much as Task 1.
            $overallWritingBand = round((($taskScores[1] + (2 * $taskScores[2])) / 3) * 2) / 2;
        }

        $metadata = $submission->metadata ?? [];
        $metadata['writing_evaluations'] = $evaluations;
        $metadata['writing_task_statuses'] = $taskStatuses;
        $metadata['writing_evaluation_status'] = $isFullyGraded
            ? 'graded'
            : (in_array('failed', $taskStatuses, true) ? 'failed' : 'incomplete');

        return $this->persistAiResult($submission, $overallWritingBand, [
            'writing_evaluations' => $evaluations,
            'writing_task_statuses' => $taskStatuses,
            'writing_evaluation_status' => $metadata['writing_evaluation_status'],
        ], count($taskScores));
    }

    /**
     * Evaluate a saved Speaking recording as advisory feedback.
     */
    public function scoreSpeakingSubmission(IeltsSubmission $submission): IeltsSubmission
    {
        $metadata = $submission->metadata ?? [];
        $recording = data_get($metadata, 'speaking_recording');
        $evaluation = null;
        $aiBand = null;
        $evaluationStatus = 'missing';

        if (is_array($recording) && filled($recording['path'] ?? null)) {
            $disk = Storage::disk($recording['disk'] ?? 'local');
            if ($disk->exists($recording['path'])) {
                try {
                    $snapshot = app(IeltsExamSnapshotService::class)->apply($submission);
                    $taskContext = collect($snapshot['groups'] ?? $submission->section?->questionGroups ?? [])
                        ->map(function ($group): string {
                            $questions = $group->questions
                                ->map(fn ($question) => 'Question ' . $question->question_number . ': ' . trim((string) $question->prompt))
                                ->filter(fn ($prompt) => trim($prompt) !== '')
                                ->implode("\n");

                            return trim(implode("\n", array_filter([
                                $group->title ? 'Topic: ' . $group->title : null,
                                $group->instruction ? 'Instructions: ' . $group->instruction : null,
                                $questions ?: null,
                            ])));
                        })
                        ->filter()
                        ->implode("\n\n");

                    $evaluation = $this->geminiService->evaluateSpeaking(
                        audioBytes: $disk->get($recording['path']),
                        mimeType: $recording['mime_type'] ?? 'audio/webm',
                        taskContext: $taskContext,
                    );

                    $evaluation = IeltsAiResultValidator::validate($evaluation, 'speaking');
                    if (($evaluation['gradable'] ?? true) === false) {
                        return $this->persistAiResult($submission, null, ['speaking_evaluation_status' => 'ungradable', 'speaking_evaluation' => $evaluation]);
                    }
                    $score = $evaluation['overallScore'] ?? null;
                    if (! is_numeric($score) || ! is_finite((float) $score) || (float) $score < 0 || (float) $score > 9) {
                        throw new \UnexpectedValueException('Speaking evaluator returned an invalid overallScore.');
                    }

                    $aiBand = (float) $score;
                    $evaluationStatus = 'graded';
                } catch (\Throwable $e) {
                    Log::error('Gemini Speaking Evaluation Error: ' . $e->getMessage(), [
                        'submission_id' => $submission->id,
                    ]);
                    $evaluation = null;
                    $evaluationStatus = 'failed';
                }
            } else {
                $evaluationStatus = 'missing_file';
            }
        }

        $metadata['speaking_evaluation_status'] = $evaluationStatus;
        unset($metadata['speaking_evaluation']);
        if ($evaluation !== null) {
            $metadata['speaking_evaluation'] = $evaluation;
        }

        return $this->persistAiResult($submission, $aiBand, [
            'speaking_evaluation_status' => $evaluationStatus,
            'speaking_evaluation' => $evaluation,
        ]);
    }

    private function persistAiResult(IeltsSubmission $submission, ?float $band, array $evaluation, ?int $raw = null): IeltsSubmission
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($submission, $band, $evaluation, $raw) {
            $current = IeltsSubmission::lockForUpdate()->findOrFail($submission->id);
            if (data_get($current->metadata, 'ai_assessment.id') !== data_get($submission->metadata, 'ai_assessment.id')) return $current;
            $metadata = array_replace($current->metadata ?? [], $evaluation);
            $metadata['ai_provenance'] = [
                'model' => $this->geminiService->modelName(), 'prompt_version' => 'ielts-2026-10-02-v2',
                'evaluated_at' => now()->toIso8601String(),
            ];
            $data = ['ai_band_score' => $band, 'metadata' => $metadata];
            if ($raw !== null) $data['raw_score'] = $raw;
            $current->update($data);
            return $current->fresh();
        });
    }

    private function duration(IeltsSubmission $submission): int
    {
        $deadline = data_get($submission->metadata, 'deadline_at');
        $end = $deadline ? min(now()->timestamp, \Illuminate\Support\Carbon::parse($deadline)->timestamp) : now()->timestamp;
        return max(0, $end - $submission->started_at->timestamp);
    }

    /**
     * Apply answer normalization that matches the interaction type.
     */
    private function checkQuestionAnswer(?string $userAnswer, IeltsQuestion $question): bool
    {
        if ($userAnswer === null || $question->correct_answer === null) {
            return false;
        }

        $group = $question->questionGroup;
        $type = $group?->question_type;
        $type = $type instanceof \BackedEnum ? $type->value : (string) $type;

        if ($group?->response_mode === 'drag_drop' || $type === \App\Enums\IeltsQuestionTypeEnum::DRAG_DROP->value) {
            return $this->matchesAnswerAlternatives($userAnswer, $question->correct_answer, fn ($user, $target) =>
                mb_strtolower(trim($user), 'UTF-8') === mb_strtolower(trim($target), 'UTF-8')
            );
        }

        if (in_array($type, [
            \App\Enums\IeltsQuestionTypeEnum::MULTIPLE_CHOICE->value,
            \App\Enums\IeltsQuestionTypeEnum::MATCHING_HEADINGS->value,
            \App\Enums\IeltsQuestionTypeEnum::MATCHING_INFORMATION->value,
            \App\Enums\IeltsQuestionTypeEnum::MAP_LABELING->value,
        ], true)) {
            return $this->matchesAnswerAlternatives($userAnswer, $question->correct_answer, fn ($user, $target) =>
                mb_strtoupper(trim($user), 'UTF-8') === mb_strtoupper(trim($target), 'UTF-8')
            );
        }

        if (in_array($type, [
            \App\Enums\IeltsQuestionTypeEnum::FILL_IN_BLANKS->value,
            \App\Enums\IeltsQuestionTypeEnum::SHORT_ANSWER->value,
        ], true)) {
            return $this->matchesAnswerAlternatives($userAnswer, $question->correct_answer, fn ($user, $target) =>
                $this->isTypedAnswerMatch($user, $target)
            );
        }

        // TFNG/YNNG and legacy rows keep their established shorthand behavior.
        return $this->checkAnswer($userAnswer, $question->correct_answer);
    }

    private function matchesAnswerAlternatives(string $userAnswer, string $correctAnswer, callable $matches): bool
    {
        $decoded = json_decode($correctAnswer, true);
        if (is_array($decoded)) {
            foreach ($decoded as $alternative) {
                if (is_scalar($alternative) && $matches($userAnswer, (string) $alternative)) {
                    return true;
                }
            }
            return false;
        }

        foreach (preg_split('/[\\/|;]/', $correctAnswer) ?: [] as $alternative) {
            if ($matches($userAnswer, trim($alternative))) {
                return true;
            }
        }

        return false;
    }

    private function isTypedAnswerMatch(string $user, string $target): bool
    {
        $normalize = function (string $answer): string {
            $answer = mb_strtolower(trim($answer), 'UTF-8');
            $answer = str_replace(['’', '‘'], "'", $answer);
            $answer = str_replace(['£', '$', '€', '₫'], '', $answer);
            $answer = rtrim($answer, '.,:;!?');
            $answer = preg_replace('/\\s+/u', ' ', $answer) ?? $answer;
            return trim($answer);
        };

        $normalizedUser = $normalize($user);
        $normalizedTarget = $normalize($target);
        if ($normalizedUser === $normalizedTarget) {
            return true;
        }

        // IELTS typed answers commonly vary by spacing or hyphenation.
        $compactUser = str_replace(['-', ' '], '', $normalizedUser);
        $compactTarget = str_replace(['-', ' '], '', $normalizedTarget);
        if ($compactUser !== '' && $compactUser === $compactTarget) {
            return true;
        }

        // Permit formatting spaces/parentheses in long numeric answers such as phone numbers.
        $numericChars = '/^[0-9\\s()+.-]+$/';
        if (preg_match($numericChars, $user) && preg_match($numericChars, $target)) {
            $userDigits = preg_replace('/\\D+/', '', $user);
            $targetDigits = preg_replace('/\\D+/', '', $target);
            if (strlen($userDigits) >= 5 && $userDigits === $targetDigits) {
                return true;
            }
        }

        return false;
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
