<?php

namespace App\Http\Controllers;

use App\Enums\IeltsSubmissionStatusEnum;
use App\Models\IeltsQuestion;
use App\Models\IeltsSection;
use App\Models\IeltsSubmission;
use App\Models\IeltsTest;
use App\Models\IeltsUserAnswer;
use App\Services\IeltsScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IeltsExamController extends Controller
{
    protected IeltsScoringService $scoringService;

    public function __construct(IeltsScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Danh sách đề thi IELTS
     */
    public function index()
    {
        $tests = IeltsTest::where('is_published', true)
            ->with(['sections' => function ($q) {
                $q->where('is_active', true);
            }])
            ->latest()
            ->get();

        $sections = IeltsSection::where('is_active', true)
            ->latest()
            ->get();

        $userSubmissions = null;
        if (Auth::check()) {
            $userSubmissions = IeltsSubmission::where('user_id', Auth::id())
                ->with(['test', 'section'])
                ->latest()
                ->take(5)
                ->get();
        }

        return view('ielts.index', compact('tests', 'sections', 'userSubmissions'));
    }

    /**
     * Trang hướng dẫn và kiểm tra thiết bị trước khi vào thi
     */
    public function show(string $slug)
    {
        $test = IeltsTest::where('slug', $slug)
            ->with(['sections.questionGroups.questions'])
            ->firstOrFail();

        return view('ielts.show', compact('test'));
    }

    /**
     * Bắt đầu làm bài thi - Khởi tạo phòng thi
     */
    public function start(Request $request, string $slug)
    {
        $test = IeltsTest::where('slug', $slug)
            ->with(['sections.questionGroups.questions'])
            ->firstOrFail();

        $targetSection = null;
        if ($request->filled('section_id')) {
            $targetSection = $test->sections()->where('ielts_sections.id', $request->input('section_id'))->first();
        } elseif ($request->filled('skill')) {
            $targetSection = $test->sections()->where('ielts_sections.skill', $request->input('skill'))->first();
        }
        if (!$targetSection) {
            $targetSection = $test->sections->first();
        }
        if (!$targetSection) {
            return back()->with('error', 'Bộ đề này chưa có nội dung phần thi.');
        }

        $userId = Auth::id();
        $candidateNumber = 'IDP-' . strtoupper(Str::random(6));

        // Tạo submission mới
        $submission = IeltsSubmission::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'ielts_test_id' => $test->id,
            'ielts_section_id' => $targetSection->id,
            'skill' => $targetSection->skill->value,
            'test_type' => $test->type,
            'status' => IeltsSubmissionStatusEnum::IN_PROGRESS,
            'started_at' => now(),
            'total_questions' => $targetSection->total_questions ?: 40,
            'metadata' => [
                'candidate_number' => $candidateNumber,
                'room_number' => 'VN-008',
                'tab_switch_count' => 0,
            ],
        ]);

        // Tạo sẵn các bản ghi user_answers cho từng câu hỏi
        $allQuestions = $targetSection->questions;
        foreach ($allQuestions as $question) {
            IeltsUserAnswer::create([
                'ielts_submission_id' => $submission->id,
                'ielts_question_id' => $question->id,
                'user_answer' => null,
                'is_flagged_for_review' => false,
                'time_spent_seconds' => 0,
            ]);
        }

        return redirect()->route('ielts.exam.room', $submission->id);
    }

    /**
     * Giao diện phòng thi giả lập chuẩn IDP / British Council
     */
    public function room(string $submissionId)
    {
        $submission = IeltsSubmission::with([
            'test',
            'section.questionGroups.questions',
            'userAnswers',
            'user',
        ])->findOrFail($submissionId);

        if ($submission->status === IeltsSubmissionStatusEnum::COMPLETED) {
            return redirect()->route('ielts.exam.result', $submission->id);
        }

        // Tính thời gian còn lại chuẩn xác theo timestamp
        $timeLimitMinutes = $submission->section->time_limit_minutes ?: 60;
        $totalSeconds = $timeLimitMinutes * 60;
        $startedAt = $submission->started_at ? \Carbon\Carbon::parse($submission->started_at) : now();
        $elapsedSeconds = max(0, now()->timestamp - $startedAt->timestamp);

        // Nếu đã quá thời gian làm bài, tự động thu bài và chuyển sang trang kết quả
        if ($elapsedSeconds >= $totalSeconds) {
            $submission = $this->scoringService->scoreSubmission($submission);
            return redirect()->route('ielts.exam.result', $submission->id);
        }

        $remainingSeconds = max(1, $totalSeconds - $elapsedSeconds);

        // Map đáp án người dùng theo question_id
        $userAnswersMap = $submission->userAnswers->keyBy('ielts_question_id');

        return view('ielts.room', compact('submission', 'remainingSeconds', 'userAnswersMap'));
    }

    /**
     * API Lưu đáp án ngầm (Auto-save) & Flag review
     */
    public function saveAnswer(Request $request, string $submissionId): JsonResponse
    {
        $submission = IeltsSubmission::findOrFail($submissionId);

        if ($submission->status === IeltsSubmissionStatusEnum::COMPLETED) {
            return response()->json(['error' => 'Test already completed'], 400);
        }

        $questionId = $request->input('question_id');
        $answer = $request->input('answer');
        $isFlagged = $request->input('is_flagged');
        $notes = $request->input('notes');

        $userAnswer = IeltsUserAnswer::where('ielts_submission_id', $submission->id)
            ->where('ielts_question_id', $questionId)
            ->first();

        if ($userAnswer) {
            $dataToUpdate = [];
            if ($request->has('answer')) {
                $dataToUpdate['user_answer'] = $answer;
            }
            if ($request->has('is_flagged')) {
                $dataToUpdate['is_flagged_for_review'] = (bool) $isFlagged;
            }
            if ($request->has('notes')) {
                $dataToUpdate['notes'] = $notes;
            }

            $userAnswer->update($dataToUpdate);
        }

        // Cập nhật log tab switch nếu có
        if ($request->has('tab_switched')) {
            $meta = $submission->metadata ?? [];
            $meta['tab_switch_count'] = ($meta['tab_switch_count'] ?? 0) + 1;
            $submission->update(['metadata' => $meta]);
        }

        return response()->json([
            'status' => 'success',
            'question_id' => $questionId,
        ]);
    }

    /**
     * Nộp bài thi
     */
    public function submit(Request $request, string $submissionId)
    {
        $submission = IeltsSubmission::findOrFail($submissionId);

        // Nếu có gửi kèm answers tổng trong payload submit
        if ($request->has('answers') && is_array($request->input('answers'))) {
            foreach ($request->input('answers') as $qId => $ans) {
                IeltsUserAnswer::where('ielts_submission_id', $submission->id)
                    ->where('ielts_question_id', $qId)
                    ->update(['user_answer' => $ans]);
            }
        }

        // Chấm điểm tự động và tính band score
        $submission = $this->scoringService->scoreSubmission($submission);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'redirect_url' => route('ielts.exam.result', $submission->id),
            ]);
        }

        return redirect()->route('ielts.exam.result', $submission->id);
    }

    /**
     * Xem kết quả thi và phân tích bài làm chi tiết
     */
    public function result(string $submissionId)
    {
        $submission = IeltsSubmission::with([
            'test',
            'section.questionGroups.questions',
            'userAnswers.question.questionGroup',
            'user',
        ])->findOrFail($submissionId);

        $answers = $submission->userAnswers->sortBy('question.question_number');
        $correctCount = $submission->raw_score;
        $totalQuestions = $submission->total_questions;
        $percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

        return view('ielts.result', compact('submission', 'answers', 'correctCount', 'totalQuestions', 'percentage'));
    }
}
