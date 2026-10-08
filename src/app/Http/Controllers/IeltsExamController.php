<?php

namespace App\Http\Controllers;

use App\Enums\IeltsSubmissionStatusEnum;
use App\Enums\RoleEnum;
use App\Models\IeltsSection;
use App\Models\IeltsSubmission;
use App\Models\IeltsTest;
use App\Models\IeltsUserAnswer;
use App\Services\IeltsScoringService;
use App\Services\IeltsMultiSelectService;
use App\Services\IeltsDragDropService;
use App\Services\IeltsMatchingInformationService;
use App\Services\IeltsWordLimitService;
use App\Services\IeltsSpeakingRecordingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IeltsExamController extends Controller
{
    protected IeltsScoringService $scoringService;

    public function __construct(IeltsScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    public function index()
    {
        $tests = IeltsTest::where('is_published', true)
            ->whereHas('sections', fn ($query) => $query->where('is_active', true)->whereHas('questionGroups.questions'))
            ->with(['sections' => fn ($query) => $query->where('is_active', true)->whereHas('questionGroups.questions')])
            ->latest()->get();
        $sections = IeltsSection::where('is_active', true)->whereHas('questionGroups.questions')->latest()->get();
        $userSubmissions = Auth::check()
            ? IeltsSubmission::where('user_id', Auth::id())->with(['test', 'section'])->latest()->take(5)->get()
            : null;

        return view('ielts.index', compact('tests', 'sections', 'userSubmissions'));
    }

    public function show(string $slug)
    {
        $preview = request()->boolean('preview') && Auth::user()?->hasAnyRole(['admin', 'super-admin', 'editor']);
        $test = IeltsTest::where('slug', $slug)->when(! $preview, fn ($query) => $query->where('is_published', true))
            ->with(['sections' => fn ($query) => $query->where('is_active', true)->whereHas('questionGroups.questions')->with('questionGroups.questions')])
            ->firstOrFail();
        abort_if($test->sections->isEmpty(), 404);

        return view('ielts.show', compact('test'));
    }

    public function start(Request $request, string $slug)
    {
        $validated = $request->validate([
            'section_id' => ['nullable', 'integer'],
            'skill' => ['nullable', 'string', 'in:listening,reading,writing,speaking'],
        ]);
        $test = IeltsTest::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $eligible = $test->sections()->where('ielts_sections.is_active', true)->whereHas('questionGroups.questions');
        if (! empty($validated['section_id'])) {
            $eligible->where('ielts_sections.id', $validated['section_id']);
        }
        if (! empty($validated['skill'])) {
            $eligible->where('ielts_sections.skill', $validated['skill']);
        }
        $sections = $eligible->get();
        if ($sections->count() !== 1) {
            return back()->withErrors(['section_id' => $sections->isEmpty()
                ? 'Phần thi không tồn tại, chưa bật hoặc không thuộc bộ đề này.'
                : 'Hãy chọn chính xác một phần thi trước khi bắt đầu.'])->withInput();
        }
        $section = $sections->first();
        $existing = IeltsSubmission::where('ielts_test_id', $test->id)
            ->where('ielts_section_id', $section->id)->where('status', 'in_progress')
            ->where('user_id', Auth::id())->latest()->get()->first(function ($candidate) {
                if ($this->deadlinePassed($candidate) && ! IeltsSpeakingRecordingService::canFinalize($candidate)) return false;
                if (Auth::check()) return true;
                $secret = session()->get($this->guestSessionKey($candidate->id));
                $hash = data_get($candidate->metadata, 'guest_access_hash');
                return is_string($secret) && is_string($hash) && hash_equals($hash, hash('sha256', $secret));
            });
        if ($existing) return redirect()->route('ielts.exam.room', $existing->id);

        $examSnapshot = app(\App\Services\IeltsExamSnapshotService::class)->capture($test, $section);
        $questionSnapshots = collect($examSnapshot['groups'])->flatMap(fn ($group) => $group['questions']);
        $questionCount = $questionSnapshots->count();
        if ($questionCount < 1) {
            return back()->withErrors(['section_id' => 'Phần thi này chưa có câu hỏi.'])->withInput();
        }

        $id = (string) Str::uuid();
        $startedAt = now();
        $minutes = max(1, (int) ($section->time_limit_minutes ?: 60));
        $metadata = [
            'candidate_number' => 'IDP-'.strtoupper(Str::random(6)),
            'room_number' => 'VN-008',
            'tab_switch_count' => 0,
            'lifecycle_version' => 2,
            'deadline_at' => $startedAt->copy()->addMinutes($minutes)->toIso8601String(),
            'exam_snapshot' => $examSnapshot,
        ];
        $guestSecret = null;
        if (Auth::guest()) {
            $guestSecret = Str::random(64);
            $metadata['guest_access_hash'] = hash('sha256', $guestSecret);
        }

        $submission = DB::transaction(function () use ($id, $startedAt, $metadata, $test, $section, $questionCount, $questionSnapshots) {
            $submission = IeltsSubmission::create([
                'id' => $id, 'user_id' => Auth::id(),
                'ielts_test_id' => $test->id, 'ielts_section_id' => $section->id,
                'skill' => $section->skill->value, 'test_type' => $test->type,
                'status' => IeltsSubmissionStatusEnum::IN_PROGRESS, 'started_at' => $startedAt,
                'total_questions' => $questionCount, 'metadata' => $metadata,
            ]);
            foreach ($questionSnapshots as $question) {
                IeltsUserAnswer::create([
                    'ielts_submission_id' => $submission->id,
                    'ielts_question_id' => $question['id'],
                    'question_snapshot_id' => $question['id'],
                    'user_answer' => null,
                    'is_flagged_for_review' => false,
                    'time_spent_seconds' => 0,
                ]);
            }
            return $submission;
        });
        if ($guestSecret !== null) {
            session()->put($this->guestSessionKey($submission->id), $guestSecret);
        }

        return redirect()->route('ielts.exam.room', $submission->id);
    }

    public function room(string $submissionId)
    {
        $submission = IeltsSubmission::with([
            'test', 'section.questionGroups.questions', 'section.questionGroups.answerOptions',
            'userAnswers', 'user',
        ])->findOrFail($submissionId);
        $this->authorizeSubmission($submission);
        if ($submission->status === IeltsSubmissionStatusEnum::ABANDONED) abort(404);
        app(\App\Services\IeltsExamSnapshotService::class)->apply($submission);
        if ($submission->status === IeltsSubmissionStatusEnum::COMPLETED) {
            return redirect()->route('ielts.exam.result', $submission->id);
        }
        abort_unless($submission->section, 404);

        if ($this->deadlinePassed($submission) && ! IeltsSpeakingRecordingService::canFinalize($submission)) {
            $submission = DB::transaction(function () use ($submissionId) {
                $locked = IeltsSubmission::lockForUpdate()->findOrFail($submissionId);
                $this->authorizeSubmission($locked);
                if ($locked->skill === 'speaking') app(IeltsSpeakingRecordingService::class)->promoteCheckpoint($locked);
                return $locked->status === IeltsSubmissionStatusEnum::COMPLETED
                    ? $locked : $this->scoringService->scoreSubmission($locked);
            });
            return redirect()->route('ielts.exam.result', $submission->id);
        }

        $remainingSeconds = max(0, now()->diffInSeconds($this->deadlineAt($submission), false));
        $userAnswersMap = $submission->userAnswers->keyBy(fn ($answer) => $answer->question_snapshot_id ?: $answer->ielts_question_id);

        return view('ielts.room', compact('submission', 'remainingSeconds', 'userAnswersMap'));
    }

    public function saveAnswer(Request $request, string $submissionId): JsonResponse
    {
        return DB::transaction(function () use ($request, $submissionId): JsonResponse {
            $submission = IeltsSubmission::lockForUpdate()->findOrFail($submissionId);
            $this->authorizeSubmission($submission);
            if ($submission->status !== IeltsSubmissionStatusEnum::IN_PROGRESS) {
                return response()->json(['error' => 'Test is no longer in progress'], 409);
            }
            if ($this->deadlinePassed($submission)) {
                return response()->json([
                    'error' => 'The test deadline has passed',
                    'redirect_url' => route('ielts.exam.result', $submission->id),
                ], 409);
            }

            $request->validate(['expected_revision' => ['sometimes', 'integer', 'min:0']]);
            if ($request->has('expected_revision') && (int) $request->input('expected_revision') !== (int) $submission->save_revision) {
                return response()->json(['error' => 'Bài làm đã thay đổi ở một cửa sổ khác. Hãy tải lại trang trước khi tiếp tục.'], 409);
            }
            $submission->increment('save_revision');
            $snapshot = app(\App\Services\IeltsExamSnapshotService::class)->apply($submission);
            if ($snapshot === null) {
                $submission->load('section.questionGroups.questions', 'section.questionGroups.answerOptions');
            }
            $snapshotQuestions = $snapshot['questions'] ?? [];

            if ($request->has('highlights')) {
                $validated = $request->validate([
                    'highlights' => ['required', 'array', 'max:200'],
                    'highlights.*.container' => ['required', 'string', 'regex:/^(passage|question)-content-[0-9]+$/'],
                    'highlights.*.start' => ['required', 'integer', 'min:0'],
                    'highlights.*.end' => ['required', 'integer', 'min:1'],
                    'highlights.*.note' => ['nullable', 'string', 'max:500'],
                ]);
                foreach ($validated['highlights'] as $highlight) {
                    preg_match('/(?:passage|question)-content-([0-9]+)/', $highlight['container'], $matches);
                    abort_unless($submission->section?->questionGroups->contains(fn ($group) => (string) $group->id === (string) ($matches[1] ?? 0)), 422, 'Vùng ghi chú không thuộc phần thi này.');
                    abort_if((int) $highlight['end'] <= (int) $highlight['start'], 422, 'Vị trí ghi chú không hợp lệ.');
                }
                $metadata = $submission->metadata ?? [];
                $metadata['highlights'] = array_values($validated['highlights']);
                $submission->update(['metadata' => $metadata]);

                return response()->json(['status' => 'success', 'revision' => (int) $submission->save_revision]);
            }

            if ($request->has('audio_progress_seconds')) {
                $request->validate(['audio_progress_seconds' => ['required', 'integer', 'min:0', 'max:86400']]);
                abort_unless($submission->skill === 'listening', 422, 'Chỉ có thể lưu tiến độ audio cho Listening.');
                $metadata = $submission->metadata ?? [];
                $metadata['audio_progress_seconds'] = max(
                    (int) ($metadata['audio_progress_seconds'] ?? 0),
                    (int) $request->input('audio_progress_seconds')
                );
                $submission->update(['metadata' => $metadata]);

                return response()->json(['status' => 'success', 'revision' => (int) $submission->save_revision, 'audio_progress_seconds' => $metadata['audio_progress_seconds']]);
            }

            if ($request->boolean('audio_ended')) {
                $request->validate(['audio_ended' => ['required', 'boolean']]);
                abort_unless($submission->skill === 'listening', 422, 'Chỉ có thể kết thúc audio cho Listening.');
                $metadata = $submission->metadata ?? [];
                if (empty($metadata['audio_completed_at'])) {
                    $candidate = now()->addSeconds(120);
                    $deadline = $this->deadlineAt($submission);
                    if ($candidate->lessThan($deadline)) {
                        $deadline = $candidate;
                    }
                    $metadata['audio_completed_at'] = now()->toIso8601String();
                    $metadata['deadline_at'] = $deadline->toIso8601String();
                    $submission->update(['metadata' => $metadata]);
                }

                return response()->json([
                    'status' => 'success',
                    'revision' => (int) $submission->save_revision,
                    'deadline_at' => data_get($submission->fresh()->metadata, 'deadline_at'),
                ]);
            }

            if ($request->has('multi_group_id')) {
                $request->validate([
                    'multi_group_id' => ['required', 'integer'],
                    'selected' => ['present', 'array'],
                    'selected.*' => ['required', 'string'],
                ]);
                $group = $submission->section?->questionGroups->first(fn ($group) => (string) $group->id === (string) $request->input('multi_group_id'));
                abort_unless($group, 404);
                abort_unless(IeltsMultiSelectService::enabled($group), 422, 'Nhóm này không hỗ trợ chọn nhiều đáp án.');
                $selected = array_values($request->input('selected'));
                abort_if(count($selected) > $group->questions->count(), 422, 'Bạn đã chọn quá số đáp án cho phép.');
                $overrides = [];
                foreach ($group->questions->values() as $index => $question) $overrides[$question->id] = $selected[$index] ?? null;
                app(IeltsMultiSelectService::class)->validateAnswers($submission, $overrides);
                foreach ($overrides as $questionId => $value) {
                    $submission->userAnswers()->where(function ($query) use ($questionId) {
                        $query->where('ielts_question_id', $questionId)->orWhere('question_snapshot_id', $questionId);
                    })->update(['user_answer' => $value]);
                }
                return response()->json(['status' => 'success', 'revision' => (int) $submission->save_revision, 'answers' => $overrides]);
            }

            if ($request->has('drag_move')) {
                $request->validate([
                    'drag_move' => ['required', 'array'],
                    'drag_move.group_id' => ['required', 'integer'],
                    'drag_move.target_question_id' => ['required', 'integer'],
                    'drag_move.source_question_id' => ['nullable', 'integer'],
                    'drag_move.answer' => ['required', 'string'],
                ]);
                $move = $request->input('drag_move');
                $group = $submission->section?->questionGroups->first(fn ($group) => (string) $group->id === (string) $move['group_id']);
                abort_unless($group, 404);
                abort_unless(IeltsDragDropService::enabled($group), 422, 'Nhóm này không hỗ trợ kéo thả.');

                $questionIds = $group->questions->pluck('id')->map(fn ($id) => (string) $id)->all();
                $targetId = (string) $move['target_question_id'];
                $sourceId = isset($move['source_question_id']) ? (string) $move['source_question_id'] : null;
                abort_unless(in_array($targetId, $questionIds, true)
                    && ($sourceId === null || in_array($sourceId, $questionIds, true)), 422, 'Ô kéo thả không thuộc nhóm này.');

                $answerForQuestion = fn (string $id) => $submission->userAnswers()->where(function ($query) use ($id) {
                    $query->where('ielts_question_id', $id)->orWhere('question_snapshot_id', $id);
                })->firstOrFail();
                $targetAnswer = $answerForQuestion($targetId);
                $sourceAnswer = $sourceId !== null ? $answerForQuestion($sourceId) : null;
                if ($sourceAnswer && (string) $sourceAnswer->user_answer !== (string) $move['answer']) {
                    abort(409, 'Đáp án nguồn đã thay đổi. Hãy thử kéo lại.');
                }

                $overrides = [$targetId => (string) $move['answer']];
                if ($sourceId !== null && $sourceId !== $targetId) $overrides[$sourceId] = null;
                app(IeltsDragDropService::class)->validateAnswers($submission, $overrides);

                if ($sourceId !== null && $sourceId !== $targetId) $sourceAnswer->update(['user_answer' => null]);
                $targetAnswer->update(['user_answer' => (string) $move['answer']]);

                return response()->json(['status' => 'success', 'revision' => (int) $submission->save_revision, 'answers' => $overrides]);
            }

            $request->validate([
                'question_id' => ['nullable', 'integer'], 'answer' => ['nullable', 'string', 'max:60000'],
                'is_flagged' => ['sometimes', 'boolean'], 'notes' => ['nullable', 'string', 'max:2000'],
                'tab_switched' => ['sometimes', 'boolean'],
            ]);
            $questionId = $request->input('question_id');
            $answer = $request->input('answer');
            $userAnswer = IeltsUserAnswer::where('ielts_submission_id', $submission->id)
                ->where(function ($query) use ($questionId) {
                    $query->where('ielts_question_id', $questionId)->orWhere('question_snapshot_id', $questionId);
                })->first();
            abort_if(! $userAnswer && ! $request->has('tab_switched'), 422, 'Câu hỏi không thuộc lượt thi này.');
            if ($userAnswer) {
                $snapshotQuestion = $snapshotQuestions[(string) $questionId] ?? null;
                if ($snapshotQuestion) $userAnswer->setRelation('question', $snapshotQuestion);
                if ($request->has('answer')) $this->validateStandardChoice($userAnswer->question, $answer);
                if ($request->has('answer') && IeltsWordLimitService::appliesTo($userAnswer->question)) {
                    app(IeltsWordLimitService::class)->validateAnswers($submission, [$questionId => $answer]);
                }
                if ($request->has('answer') && IeltsMultiSelectService::enabled($userAnswer->question?->questionGroup)) {
                    app(IeltsMultiSelectService::class)->validateAnswers($submission, [$questionId => $answer]);
                }
                if ($request->has('answer') && IeltsDragDropService::enabled($userAnswer->question?->questionGroup)) {
                    app(IeltsDragDropService::class)->validateAnswers($submission, [$questionId => $answer]);
                }
                if ($request->has('answer') && $userAnswer->question?->questionGroup?->question_type?->value === 'matching_information'
                    && $userAnswer->question->questionGroup->response_mode !== 'drag_drop') {
                    app(IeltsMatchingInformationService::class)->validateAnswers($submission, [$questionId => $answer]);
                }
                $dataToUpdate = [];
                if ($request->has('answer')) $dataToUpdate['user_answer'] = $answer;
                if ($request->has('is_flagged')) $dataToUpdate['is_flagged_for_review'] = (bool) $request->input('is_flagged');
                if ($request->has('notes')) $dataToUpdate['notes'] = $request->input('notes');
                $userAnswer->update($dataToUpdate);
            }
            if ($request->has('tab_switched')) {
                $meta = $submission->metadata ?? [];
                $meta['tab_switch_count'] = ($meta['tab_switch_count'] ?? 0) + 1;
                $submission->update(['metadata' => $meta]);
            }

            return response()->json(['status' => 'success', 'revision' => (int) $submission->save_revision, 'question_id' => $questionId]);
        });
    }

    public function startSpeakingRecording(string $submissionId): JsonResponse
    {
        $submission = IeltsSubmission::findOrFail($submissionId);
        $this->authorizeSubmission($submission);
        return response()->json(app(IeltsSpeakingRecordingService::class)->start($submissionId));
    }

    public function uploadSpeakingRecording(Request $request, string $submissionId): JsonResponse
    {
        $submission = IeltsSubmission::findOrFail($submissionId);
        $this->authorizeSubmission($submission);
        $data = $request->validate([
            'recording' => ['required', 'file', 'max:12288', 'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/aac,audio/m4a,audio/x-m4a'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:7200'],
            'recording_id' => ['required', 'uuid'],
            'revision' => ['required', 'integer', 'min:1'],
            'final' => ['required', 'boolean'],
        ]);
        $data['final'] = $request->boolean('final');
        return response()->json(app(IeltsSpeakingRecordingService::class)->upload($submissionId, $data['recording'], $data));
    }

    public function finalizeSpeakingRecording(string $submissionId): JsonResponse
    {
        return DB::transaction(function () use ($submissionId): JsonResponse {
            $submission = IeltsSubmission::lockForUpdate()->findOrFail($submissionId);
            $this->authorizeSubmission($submission);
            abort_unless($submission->status->value === 'in_progress'
                && IeltsSpeakingRecordingService::canFinalize($submission), 409, 'Đã hết thời gian khôi phục.');
            abort_unless(app(IeltsSpeakingRecordingService::class)->promoteCheckpoint($submission), 422, 'Chưa có bản ghi trên server để khôi phục.');
            return response()->json(['status' => 'saved', 'playback_url' => route('ielts.exam.speaking-recording', $submissionId)]);
        });
    }

    public function deleteSpeakingRecording(string $submissionId): JsonResponse
    {
        $submission = IeltsSubmission::findOrFail($submissionId);
        $this->authorizeSubmission($submission);
        app(IeltsSpeakingRecordingService::class)->delete($submissionId);
        return response()->json(['status' => 'deleted']);
    }

    public function streamSpeakingRecording(string $submissionId)
    {
        $submission = IeltsSubmission::findOrFail($submissionId);
        $this->authorizeSubmission($submission);
        abort_unless($submission->skill === 'speaking', 404);

        $useDraft = request()->boolean('draft') && $submission->status->value === 'in_progress';
        $recording = data_get($submission->metadata, $useDraft ? 'speaking_draft' : 'speaking_recording');
        abort_unless(is_array($recording) && filled($recording['path'] ?? null), 404);
        $disk = Storage::disk($recording['disk'] ?? 'local');
        abort_unless($disk->exists($recording['path']), 404);

        return $disk->response(
            $recording['path'],
            'speaking-recording.' . ($recording['extension'] ?? 'webm'),
            [
                'Content-Type' => $recording['mime_type'] ?? 'audio/webm',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    public function submit(Request $request, string $submissionId)
    {
        $request->validate(['answers' => ['sometimes', 'array'], 'answers.*' => ['nullable', 'string', 'max:60000'], 'expected_revision' => ['sometimes', 'integer', 'min:0']]);
        $submission = DB::transaction(function () use ($request, $submissionId) {
            $submission = IeltsSubmission::lockForUpdate()->findOrFail($submissionId);
            $this->authorizeSubmission($submission);
            if ($submission->status === IeltsSubmissionStatusEnum::ABANDONED) abort(404);
            if ($submission->status === IeltsSubmissionStatusEnum::COMPLETED) return $submission;

            if ($request->has('expected_revision') && (int) $request->input('expected_revision') !== (int) $submission->save_revision) {
                abort(409, 'Bài làm đã thay đổi ở cửa sổ khác. Hãy tải lại trang trước khi nộp.');
            }
            if ($submission->skill === 'speaking' && ! $this->deadlinePassed($submission)) {
                abort_unless(
                    filled(data_get($submission->metadata, 'speaking_recording.path')),
                    422,
                    'Vui lòng thu âm và lưu câu trả lời Speaking trước khi nộp.'
                );
            }

            app(\App\Services\IeltsExamSnapshotService::class)->apply($submission);
            if (!$this->deadlinePassed($submission)) {
                foreach ($request->input('answers', []) as $questionId => $answer) {
                    $question = $submission->section->questionGroups->flatMap(fn ($group) => $group->questions)->firstWhere('id', (int) $questionId);
                    abort_unless($question, 422, 'Câu hỏi không thuộc lượt thi này.');
                    $this->validateStandardChoice($question, $answer);
                }
                app(IeltsMultiSelectService::class)->validateAnswers($submission, $request->input('answers', []));
                app(IeltsDragDropService::class)->validateAnswers($submission, $request->input('answers', []));
                app(IeltsMatchingInformationService::class)->validateAnswers($submission, $request->input('answers', []));
                app(IeltsWordLimitService::class)->validateAnswers($submission, $request->input('answers', []));
                if ($request->has('answers') && is_array($request->input('answers'))) {
                    foreach ($request->input('answers') as $questionId => $answer) {
                        IeltsUserAnswer::where('ielts_submission_id', $submission->id)
                            ->where(function ($query) use ($questionId) {
                                $query->where('ielts_question_id', $questionId)->orWhere('question_snapshot_id', $questionId);
                            })->update(['user_answer' => $answer]);
                    }
                }
            }

            if ($submission->skill === 'speaking') {
                if (IeltsSpeakingRecordingService::canFinalize($submission)) abort(409, 'Hãy dừng và lưu bản thu đang thực hiện trước khi nộp.');
                app(IeltsSpeakingRecordingService::class)->promoteCheckpoint($submission);
            }
            return $this->scoringService->scoreSubmission($submission);
        });
        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect_url' => route('ielts.exam.result', $submission->id)]);
        }
        return redirect()->route('ielts.exam.result', $submission->id);
    }

    public function result(string $submissionId)
    {
        $submission = IeltsSubmission::with([
            'test', 'section.questionGroups.questions', 'userAnswers.question.questionGroup', 'user',
        ])->findOrFail($submissionId);
        $this->authorizeSubmission($submission);
        if ($submission->status === IeltsSubmissionStatusEnum::ABANDONED) abort(404);
        app(\App\Services\IeltsExamSnapshotService::class)->apply($submission);

        if ($submission->status === IeltsSubmissionStatusEnum::IN_PROGRESS) {
            if (!$this->deadlinePassed($submission) || IeltsSpeakingRecordingService::canFinalize($submission)) return redirect()->route('ielts.exam.room', $submission->id);
            DB::transaction(function () use ($submissionId): void {
                $locked = IeltsSubmission::lockForUpdate()->findOrFail($submissionId);
                $this->authorizeSubmission($locked);
                if ($locked->status === IeltsSubmissionStatusEnum::IN_PROGRESS) {
                    if ($locked->skill === 'speaking') app(IeltsSpeakingRecordingService::class)->promoteCheckpoint($locked);
                    $this->scoringService->scoreSubmission($locked);
                }
            });
            $submission = IeltsSubmission::with([
                'test', 'section.questionGroups.questions', 'userAnswers.question.questionGroup', 'user',
            ])->findOrFail($submissionId);
            app(\App\Services\IeltsExamSnapshotService::class)->apply($submission);
        }

        $answers = $submission->userAnswers->sortBy('question.question_number');
        $correctCount = $submission->raw_score;
        $totalQuestions = $submission->total_questions;
        $percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

        return view('ielts.result', compact('submission', 'answers', 'correctCount', 'totalQuestions', 'percentage'));
    }

    private function authorizeSubmission(IeltsSubmission $submission): void
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasAnyRole')
            && ($user->hasAnyRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])
                || (in_array(request()->route()?->getName(), ['ielts.exam.result', 'ielts.exam.speaking-recording'], true) && Gate::allows('view', $submission)))) return;

        if ($submission->user_id !== null) {
            abort_unless($user && (string) $submission->user_id === (string) $user->getAuthIdentifier(), 404);
            return;
        }
        $expectedHash = data_get($submission->metadata, 'guest_access_hash');
        $secret = session()->get($this->guestSessionKey($submission->id));
        abort_unless(is_string($expectedHash) && is_string($secret)
            && hash_equals($expectedHash, hash('sha256', $secret)), 404);
    }

    private function validateStandardChoice(?\App\Models\IeltsQuestion $question, ?string $answer): void
    {
        if (! $question || blank($answer) || IeltsDragDropService::enabled($question->questionGroup)
            || IeltsMultiSelectService::enabled($question->questionGroup)) return;
        if (! in_array($question->questionGroup?->section?->skill?->value, ['reading', 'listening'], true)) return;
        $keys = array_column($question->options ?? [], 'key');
        if (! $keys) $keys = array_keys(\App\Services\IeltsAuthoringService::fixedAnswers($question->questionGroup?->question_type?->value));
        if ($keys) {
            $normalize = fn ($value) => mb_strtoupper(trim((string) $value));
            abort_unless(in_array($normalize($answer), array_map($normalize, $keys), true), 422, 'Đáp án không nằm trong danh sách lựa chọn.');
        }
    }

    private function guestSessionKey(string $submissionId): string
    {
        return 'ielts.submission_access.'.$submissionId;
    }

    private function deadlineAt(IeltsSubmission $submission): \Illuminate\Support\Carbon
    {
        $deadline = data_get($submission->metadata, 'deadline_at');
        if (is_string($deadline) && $deadline !== '') return \Illuminate\Support\Carbon::parse($deadline);
        return $submission->started_at->copy()->addMinutes(max(1, (int) ($submission->section?->time_limit_minutes ?: 60)));
    }

    private function deadlinePassed(IeltsSubmission $submission): bool
    {
        return now()->greaterThanOrEqualTo($this->deadlineAt($submission));
    }
}
