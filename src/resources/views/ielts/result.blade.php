@extends('layouts.app')

@section('title', 'Kết Quả Bài Thi IELTS: ' . ($submission->test?->title ?? 'IELTS Test'))

@php
    $skill = $submission->skill ?? 'reading';
    $isListening = ($skill === 'listening');
    $isReading = ($skill === 'reading');
    $isWriting = ($skill === 'writing');
    $groups = $submission->section?->questionGroups ?? collect();
    $firstAudio = $groups->firstWhere('audio_url', '!=', null)?->audio_url
        ?? 'https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg';
@endphp

@section('content')
<div class="bg-gradient-to-b from-blue-50/70 via-white to-slate-50 py-12 px-4 sm:px-6 lg:px-8 min-h-screen text-slate-800"
     x-data="{
         filter: 'all',
         activeTranscriptPart: 1,
         activeWritingTask: 1,
         writingTab: 'overview',
         scrollToTranscriptQuestion(partNum, qNum) {
             this.activeTranscriptPart = partNum;
             this.$nextTick(() => {
                 const el = document.getElementById('transcript-target-q-' + qNum);
                 if (el) {
                     el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                     el.classList.add('ring-4', 'ring-blue-400', 'scale-110');
                     setTimeout(() => {
                         el.classList.remove('ring-4', 'ring-blue-400', 'scale-110');
                     }, 3000);
                 }
             });
         }
     }">
    <div class="max-w-6xl mx-auto space-y-8">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('ielts.tests.index') }}" class="hover:text-blue-600 transition-colors">Danh sách đề thi</a>
            <span>/</span>
            <span class="text-slate-700">Báo cáo kết quả &amp; Giải thích chi tiết ({{ ucfirst($skill) }})</span>
        </div>

        {{-- ===================================================== --}}
        {{-- HEADER SUMMARY CARD --}}
        {{-- ===================================================== --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-sm">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-semibold mb-3
                        @if($isListening) bg-blue-50 text-blue-700 border border-blue-100
                        @elseif($isWriting) bg-amber-50 text-amber-700 border border-amber-100
                        @else bg-emerald-50 text-emerald-700 border border-emerald-100 @endif">
                        <span>✓ Đã hoàn thành &amp; chấm điểm</span>
                        <span class="font-black">• {{ strtoupper($skill) }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                        {{ $submission->test?->title ?? ($submission->section?->title ?? 'Bài Thi IELTS') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2">
                        Thí sinh: <strong class="text-slate-800">{{ $submission->user?->name ?? 'Candidate Guest' }}</strong> •
                        Thời gian nộp: {{ $submission->completed_at ? $submission->completed_at->format('H:i d/m/Y') : now()->format('H:i d/m/Y') }}
                    </p>
                </div>

                {{-- SCORE BADGE --}}
                <div class="flex items-center gap-4 bg-slate-50 border border-slate-200 rounded-2xl p-6 flex-shrink-0">
                    <div class="text-center pr-6 border-r border-slate-200">
                        <span class="text-xs font-bold text-slate-500 block">IELTS Band</span>
                        <div class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500 my-1">
                            {{ number_format($submission->band_score, 1) }}
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600">
                            @if($submission->band_score >= 8.0) Very Good User
                            @elseif($submission->band_score >= 7.0) Good User
                            @elseif($submission->band_score >= 6.0) Competent User
                            @elseif($submission->band_score >= 5.0) Modest User
                            @else Limited User
                            @endif
                        </span>
                    </div>

                    <div class="text-center pl-2">
                        @if($isWriting)
                            <span class="text-xs font-bold text-slate-500 block">AI chấm điểm</span>
                            <div class="text-3xl font-black text-slate-900 my-1">Gemini <span class="text-slate-400 text-xl font-bold">AI</span></div>
                            <span class="text-[11px] font-semibold text-amber-600">4 tiêu chí chuẩn IELTS</span>
                        @else
                            <span class="text-xs font-bold text-slate-500 block">Điểm thô (Raw)</span>
                            <div class="text-3xl font-black text-slate-900 my-1">
                                {{ $correctCount }} <span class="text-slate-400 text-xl font-bold">/ {{ $totalQuestions }}</span>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-500">Độ chính xác: {{ $percentage }}%</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                @if($isWriting)
                    <div class="flex items-center gap-2">
                        <button type="button" @click="activeWritingTask = 1; writingTab = 'overview'"
                                :class="activeWritingTask === 1 ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold transition-colors border">
                            📊 Task 1 (Report)
                        </button>
                        <button type="button" @click="activeWritingTask = 2; writingTab = 'overview'"
                                :class="activeWritingTask === 2 ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold transition-colors border">
                            ✍️ Task 2 (Essay)
                        </button>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <button type="button" @click="filter = 'all'"
                                :class="filter === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors border">
                            Tất cả ({{ $answers->count() }})
                        </button>
                        <button type="button" @click="filter = 'correct'"
                                :class="filter === 'correct' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors border flex items-center gap-1">
                            <span>✓ Đúng</span> ({{ $answers->where('is_correct', true)->count() }})
                        </button>
                        <button type="button" @click="filter = 'incorrect'"
                                :class="filter === 'incorrect' ? 'bg-red-500 text-white border-red-500' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors border flex items-center gap-1">
                            <span>✗ Sai</span> ({{ $answers->where('is_correct', false)->count() }})
                        </button>
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    @if($submission->test)
                        <a href="{{ route('ielts.tests.show', $submission->test->slug) }}"
                           class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-blue-600/25 transition-all">
                            Làm lại bài thi này
                        </a>
                    @endif
                    <a href="{{ route('ielts.tests.index') }}"
                       class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                        Về danh sách đề thi
                    </a>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- LISTENING: AUDIO REVIEW PLAYER & TRANSCRIPT VIEWER --}}
        {{-- ===================================================== --}}
        @if($isListening)
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm"
                 x-data="{
                     audioEl: null,
                     isPlaying: false,
                     currentTime: '00:00',
                     duration: '30:00',
                     progress: 0,
                     playbackRate: 1.0,
                     volume: 85,
                     init() {
                         this.audioEl = this.$refs.reviewAudio;
                         if (this.audioEl) this.audioEl.volume = this.volume / 100;
                     },
                     togglePlay() {
                         if (!this.audioEl) return;
                         if (this.isPlaying) { this.audioEl.pause(); this.isPlaying = false; }
                         else { this.audioEl.play(); this.isPlaying = true; }
                     },
                     setRate(rate) { this.playbackRate = rate; if (this.audioEl) this.audioEl.playbackRate = rate; },
                     seek(e) {
                         if (!this.audioEl || !this.audioEl.duration) return;
                         const rect = e.currentTarget.getBoundingClientRect();
                         const pos = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                         this.audioEl.currentTime = pos * this.audioEl.duration;
                     },
                     onTimeUpdate() {
                         if (!this.audioEl) return;
                         const cur = Math.floor(this.audioEl.currentTime);
                         const dur = Math.floor(this.audioEl.duration || 1800);
                         this.currentTime = String(Math.floor(cur/60)).padStart(2,'0') + ':' + String(cur%60).padStart(2,'0');
                         this.duration = String(Math.floor(dur/60)).padStart(2,'0') + ':' + String(dur%60).padStart(2,'0');
                         this.progress = dur > 0 ? (cur / dur) * 100 : 0;
                     }
                 }">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100">🎧</div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">Audio player luyện nghe &amp; rà soát</h3>
                            <p class="text-xs text-slate-500">Phát lại, tua đến vị trí mong muốn và điều chỉnh tốc độ nghe</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 px-2">Tốc độ:</span>
                        @foreach([0.8, 1.0, 1.25] as $rate)
                            <button type="button" @click="setRate({{ $rate }})"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                    :class="playbackRate === {{ $rate }} ? 'bg-blue-600 text-white shadow' : 'text-slate-500 hover:text-slate-900'">
                                {{ $rate }}x
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <button type="button" @click="togglePlay()"
                            class="w-11 h-11 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white flex items-center justify-center text-lg font-bold shadow-lg shadow-blue-600/25 transition-all active:scale-95 flex-shrink-0">
                        <span x-text="isPlaying ? '⏸' : '▶'">▶</span>
                    </button>
                    <span class="text-xs font-mono text-slate-600 w-12 text-center" x-text="currentTime">00:00</span>
                    <div class="flex-grow bg-slate-200 rounded-full h-2.5 overflow-hidden cursor-pointer" @click="seek($event)">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-full rounded-full transition-all duration-100" :style="'width: ' + progress + '%'"></div>
                    </div>
                    <span class="text-xs font-mono text-slate-500 w-12 text-center" x-text="duration">30:00</span>
                    <audio x-ref="reviewAudio" @timeupdate="onTimeUpdate()" @ended="isPlaying = false" src="{{ $firstAudio }}" preload="auto"></audio>
                </div>
            </div>

            {{-- Transcript Viewer --}}
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                            <span>📜</span> Toàn văn audio transcript &amp; điểm chốt đáp án
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Các đoạn hội thoại được gắn thẻ câu hỏi [Qx: Đáp án] nổi bật tương ứng</p>
                    </div>
                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                        @foreach([1, 2, 3, 4] as $pIdx)
                            <button type="button" @click="activeTranscriptPart = {{ $pIdx }}"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all border"
                                    :class="activeTranscriptPart === {{ $pIdx }} ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                Part {{ $pIdx }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @foreach($groups as $idx => $group)
                    @php
                        $partNumber = $group->order ?? ($idx + 1);
                        $rawTranscript = $group->transcript ?? 'Chưa có bản ghi âm thanh cho phần này.';
                        $formattedTranscript = preg_replace_callback('/\(Q(\d+)\)/', function($m) use ($answers) {
                            $qNum = (int)$m[1];
                            $ans = $answers->firstWhere('question.question_number', $qNum);
                            $correct = $ans?->question?->correct_answer ?? '';
                            return '<span id="transcript-target-q-' . $qNum . '" class="inline-flex items-center gap-1 mx-1 px-2.5 py-0.5 rounded-lg bg-amber-100 text-amber-900 font-bold font-mono text-xs border border-amber-300 shadow-sm transition-all duration-300 cursor-pointer"><span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> [Q' . $qNum . ': ' . e($correct) . ']</span>';
                        }, e($rawTranscript));
                    @endphp
                    <div x-show="activeTranscriptPart === {{ $partNumber }}" x-cloak class="space-y-4">
                        <div class="flex items-center justify-between text-xs text-slate-500 border-b border-slate-100 pb-2">
                            <span class="font-bold uppercase text-blue-600">{{ $group->title }}</span>
                            <span class="font-mono text-[11px]">Questions {{ ($partNumber - 1) * 10 + 1 }}–{{ $partNumber * 10 }}</span>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 text-sm font-sans leading-relaxed text-slate-700 whitespace-pre-line custom-scroll max-h-[480px] overflow-y-auto">
                            {!! $formattedTranscript !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- READING: PASSAGES REVIEWER --}}
        {{-- ===================================================== --}}
        @if($isReading)
            @php $readingPassages = $groups->filter(fn ($group) => filled($group->passage_content))->values(); @endphp
            @if($readingPassages->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6"
                     x-data="{ activePassageTab: 1 }">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                                <span>📖</span> Toàn văn {{ $readingPassages->count() }} bài đọc (Reading Passages) & đối chiếu
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Đọc lại bài văn hoàn chỉnh và đối chiếu các câu hỏi của từng bài đọc</p>
                        </div>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1">
                            @foreach($readingPassages as $pIdx => $pGroup)
                                <button type="button" @click="activePassageTab = {{ $pIdx + 1 }}"
                                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all border"
                                        :class="activePassageTab === {{ $pIdx + 1 }} ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    Passage {{ $pIdx + 1 }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach($readingPassages as $pIdx => $pGroup)
                        <div x-show="activePassageTab === {{ $pIdx + 1 }}" x-cloak class="space-y-4">
                            <div class="flex items-center justify-between text-xs text-slate-500 border-b border-slate-100 pb-2">
                                <span class="font-bold uppercase text-blue-600">{{ $pGroup->title }}</span>
                                <span class="font-mono text-[11px]">
                                    @php
                                        $passageQuestionNumbers = $groups->skipUntil(fn ($group) => $group->id === $pGroup->id)
                                            ->takeUntil(fn ($group) => $group->id !== $pGroup->id && filled($group->passage_content))
                                            ->flatMap(fn ($group) => $group->questions)->pluck('question_number');
                                    @endphp
                                    @if($passageQuestionNumbers->isNotEmpty())
                                        Questions {{ $passageQuestionNumbers->min() }}–{{ $passageQuestionNumbers->max() }}
                                    @endif
                                </span>
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 text-sm font-sans leading-relaxed text-slate-700 prose max-w-none custom-scroll max-h-[500px] overflow-y-auto">
                                {!! $pGroup->passage_content !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ===================================================== --}}
        {{-- WRITING: AI EVALUATION REPORT (GEMINI) --}}
        {{-- ===================================================== --}}
        @if($isWriting)
            @php $writingEvals = $submission->metadata['writing_evaluations'] ?? []; @endphp

            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                {{-- Tab Bar --}}
                <div class="flex border-b border-slate-200 overflow-x-auto">
                    @foreach(['overview' => ['📊', 'Tổng quan & 4 tiêu chí'], 'feedback' => ['💡', 'Nhận xét & lỗi sai'], 'sample' => ['✨', 'Bài mẫu Band 8.0+'], 'submission' => ['📝', 'Bài làm của bạn']] as $tabKey => $tabInfo)
                        <button type="button" @click="writingTab = '{{ $tabKey }}'"
                                :class="writingTab === '{{ $tabKey }}' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'"
                                class="px-5 py-4 text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap">
                            <span>{{ $tabInfo[0] }}</span> {{ $tabInfo[1] }}
                        </button>
                    @endforeach
                </div>

                <div class="p-6 sm:p-8">

                    {{-- TAB: OVERVIEW + 4 CRITERIA --}}
                    <div x-show="writingTab === 'overview'" x-cloak>
                        @foreach($answers as $taskAns)
                            @php
                                $q = $taskAns->question;
                                $taskNum = $q->question_number ?? 1;
                                $evalData = json_decode($taskAns->notes ?? '{}', true) ?? [];
                                $criteria = $evalData['criteria'] ?? [];
                                $overallScore = $evalData['overallScore'] ?? ($taskNum === 1 ? 5.5 : 6.0);
                                $bandLevel = $evalData['bandLevel'] ?? ('Band ' . $overallScore . ' - Competent User');
                                $complexity = $evalData['complexity'] ?? 'B2 - C2';
                                $minWords = $taskNum === 1 ? 150 : 250;
                                $wordCount = str_word_count($taskAns->user_answer ?? '');
                                $taskLabel = $taskNum === 1 ? 'Task 1 (Academic Report)' : 'Task 2 (Discursive Essay)';
                            @endphp
                            <div x-show="activeWritingTask === {{ $taskNum }}" x-cloak>
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-8 pb-6 border-b border-slate-100">
                                    <div>
                                        <div class="text-[11px] font-bold text-blue-600 mb-1">
                                            IELTS Academic Writing – {{ $taskLabel }}
                                        </div>
                                        <div class="flex items-baseline gap-3">
                                            <span class="text-6xl font-black text-transparent bg-clip-text bg-gradient-to-br from-blue-600 to-cyan-500">
                                                {{ number_format((float)$overallScore, 1) }}
                                            </span>
                                            <div>
                                                <div class="text-lg font-black text-slate-900">/ 9.0</div>
                                                <div class="text-xs text-slate-500 mt-0.5">{{ $bandLevel }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-center px-4 py-3 bg-slate-50 rounded-2xl border border-slate-200">
                                            <div class="text-[10px] text-slate-500 font-bold mb-1">Số từ</div>
                                            <div class="text-2xl font-black {{ $wordCount >= $minWords ? 'text-emerald-600' : 'text-red-500' }}">
                                                {{ $wordCount }}
                                                <span class="text-sm text-slate-400">/ {{ $minWords }}+</span>
                                            </div>
                                        </div>
                                        <div class="text-center px-4 py-3 bg-slate-50 rounded-2xl border border-slate-200">
                                            <div class="text-[10px] text-slate-500 font-bold mb-1">Trình độ</div>
                                            <div class="text-sm font-black text-indigo-600">{{ $complexity }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($criteria))
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                        @foreach($criteria as $ci => $criterion)
                                            @php
                                                $score = (float)($criterion['score'] ?? 0);
                                                $maxScore = (float)($criterion['maxScore'] ?? 9.0);
                                                $pct = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
                                                $comment = $criterion['comment'] ?? '';
                                                $name = $criterion['name'] ?? 'Criterion';
                                                $gradients = [
                                                    ['from-blue-500 to-cyan-500', 'text-blue-700', 'bg-blue-50 border-blue-100'],
                                                    ['from-emerald-500 to-teal-500', 'text-emerald-700', 'bg-emerald-50 border-emerald-100'],
                                                    ['from-violet-500 to-purple-500', 'text-violet-700', 'bg-violet-50 border-violet-100'],
                                                    ['from-rose-500 to-pink-500', 'text-rose-700', 'bg-rose-50 border-rose-100'],
                                                ];
                                                $g = $gradients[$ci % count($gradients)];
                                            @endphp
                                            <div class="p-5 rounded-2xl border {{ $g[2] }} space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold {{ $g[1] }} leading-tight max-w-[60%]">{{ $name }}</span>
                                                    <span class="text-2xl font-black text-slate-900">
                                                        {{ number_format($score, 1) }}
                                                        <span class="text-sm text-slate-400 font-semibold">/ {{ number_format($maxScore, 1) }}</span>
                                                    </span>
                                                </div>
                                                <div class="w-full bg-white rounded-full h-2.5 overflow-hidden border border-slate-100">
                                                    <div class="h-full rounded-full bg-gradient-to-r {{ $g[0] }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                                @if($comment)
                                                    <p class="text-xs text-slate-600 leading-relaxed">{{ $comment }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center text-amber-700 text-sm mb-6">
                                        <span class="text-3xl block mb-2">⚡</span>
                                        <strong>Dữ liệu AI đang được tải...</strong>
                                        <p class="text-xs text-amber-600/80 mt-1">Kết quả đánh giá từ Gemini AI sẽ hiển thị ở đây sau khi chấm điểm hoàn tất.</p>
                                    </div>
                                @endif

                                @if($taskNum === 2)
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-xs text-slate-500">
                                        <span class="font-bold text-slate-700 block mb-1">💡 Công thức tính Band IELTS Writing Overall:</span>
                                        <span class="font-mono">Overall = (Task 1 × ⅓) + (Task 2 × ⅔) → Làm tròn về .0 hoặc .5 gần nhất</span>
                                        <div class="mt-2 font-bold text-blue-600 text-base">
                                            Kết quả tổng: <span class="text-slate-900">{{ number_format($submission->band_score, 1) }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- TAB: FEEDBACK --}}
                    <div x-show="writingTab === 'feedback'" x-cloak>
                        @foreach($answers as $taskAns)
                            @php
                                $q = $taskAns->question;
                                $taskNum = $q->question_number ?? 1;
                                $evalData = json_decode($taskAns->notes ?? '{}', true) ?? [];
                                $strengths = $evalData['strengths'] ?? [];
                                $improvements = $evalData['improvements'] ?? [];
                                $corrections = $evalData['corrections'] ?? [];
                            @endphp
                            <div x-show="activeWritingTask === {{ $taskNum }}" x-cloak class="space-y-6">

                                @if(!empty($strengths))
                                    <div>
                                        <h3 class="text-sm font-black text-emerald-700 flex items-center gap-2 mb-3">
                                            <span class="w-7 h-7 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100 text-base">💪</span>
                                            Điểm mạnh trong bài viết
                                        </h3>
                                        <div class="space-y-2">
                                            @foreach($strengths as $s)
                                                <div class="flex items-start gap-3 p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-100">
                                                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">✓</span>
                                                    <p class="text-sm text-slate-700 leading-relaxed">{{ $s }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($improvements))
                                    <div>
                                        <h3 class="text-sm font-black text-amber-700 flex items-center gap-2 mb-3">
                                            <span class="w-7 h-7 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100 text-base">🎯</span>
                                            Điểm cần cải thiện
                                        </h3>
                                        <div class="space-y-2">
                                            @foreach($improvements as $imp)
                                                <div class="flex items-start gap-3 p-3.5 bg-amber-50/60 rounded-xl border border-amber-100">
                                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">→</span>
                                                    <p class="text-sm text-slate-700 leading-relaxed">{{ $imp }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($corrections))
                                    <div>
                                        <h3 class="text-sm font-black text-red-600 flex items-center gap-2 mb-3">
                                            <span class="w-7 h-7 bg-red-50 rounded-xl flex items-center justify-center border border-red-100 text-base">🔍</span>
                                            Bảng sửa lỗi chi tiết (3–5 lỗi điển hình)
                                        </h3>
                                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                            <div class="grid grid-cols-12 bg-slate-50 px-4 py-2.5 text-[10px] font-bold text-slate-500 border-b border-slate-200">
                                                <div class="col-span-1 text-center">#</div>
                                                <div class="col-span-2">Loại lỗi</div>
                                                <div class="col-span-4 pr-2">Câu gốc (sai)</div>
                                                <div class="col-span-4">Gợi ý sửa (đúng)</div>
                                                <div class="col-span-1 text-center">¶</div>
                                            </div>
                                            @foreach($corrections as $ci => $corr)
                                                @php
                                                    $type = $corr['type'] ?? 'grammar';
                                                    $badge = $corr['badge'] ?? 'Ngữ pháp';
                                                    $original = $corr['original'] ?? '';
                                                    $suggestion = $corr['suggestion'] ?? '';
                                                    $reason = $corr['reason'] ?? '';
                                                    $para = $corr['paragraph'] ?? 1;
                                                    $badgeClass = match($type) {
                                                        'grammar' => 'bg-red-50 text-red-700 border-red-200',
                                                        'vocab'   => 'bg-violet-50 text-violet-700 border-violet-200',
                                                        default   => 'bg-blue-50 text-blue-700 border-blue-200',
                                                    };
                                                @endphp
                                                <div class="grid grid-cols-12 px-4 py-3 text-xs items-start {{ $ci % 2 === 0 ? 'bg-white' : 'bg-slate-50/50' }} border-b border-slate-100 last:border-b-0">
                                                    <div class="col-span-1 text-center">
                                                        <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[10px] mx-auto">{{ $ci + 1 }}</span>
                                                    </div>
                                                    <div class="col-span-2">
                                                        <span class="px-1.5 py-0.5 rounded border text-[10px] font-bold {{ $badgeClass }}">{{ $badge }}</span>
                                                    </div>
                                                    <div class="col-span-4 pr-3">
                                                        <p class="text-red-600 line-through font-mono leading-relaxed text-[11px]">"{{ $original }}"</p>
                                                        @if($reason)
                                                            <p class="text-slate-500 text-[10px] mt-1 leading-relaxed italic">{{ $reason }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="col-span-4">
                                                        <p class="text-emerald-700 font-mono font-bold leading-relaxed text-[11px]">"{{ $suggestion }}"</p>
                                                    </div>
                                                    <div class="col-span-1 text-center text-slate-400 text-[10px] font-mono">¶{{ $para }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(empty($strengths) && empty($improvements) && empty($corrections))
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-10 text-center">
                                        <span class="text-3xl block mb-3">🤖</span>
                                        <p class="text-slate-500 text-sm">Chưa có dữ liệu nhận xét từ Gemini AI cho Task {{ $taskNum }}.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- TAB: SAMPLE ESSAY --}}
                    <div x-show="writingTab === 'sample'" x-cloak>
                        @foreach($answers as $taskAns)
                            @php
                                $q = $taskAns->question;
                                $taskNum = $q->question_number ?? 1;
                                $evalData = json_decode($taskAns->notes ?? '{}', true) ?? [];
                                $sampleEssay = $evalData['sampleEssay'] ?? '';
                                $taskLabel = $taskNum === 1 ? 'Task 1 (Academic Report)' : 'Task 2 (Discursive Essay)';
                            @endphp
                            <div x-show="activeWritingTask === {{ $taskNum }}" x-cloak>
                                @if($sampleEssay)
                                    <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-lg shadow-lg shadow-blue-600/25">✨</div>
                                        <div>
                                            <h3 class="text-base font-black text-slate-900">Bài viết mẫu đạt Band 8.0+</h3>
                                            <p class="text-xs text-slate-500">{{ $taskLabel }} – Được tạo bởi Gemini AI</p>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 rounded-2xl p-6 border border-blue-100 text-sm text-slate-700 leading-8 font-sans whitespace-pre-line tracking-wide">
                                        {{ $sampleEssay }}
                                    </div>
                                    <p class="text-[11px] text-slate-400 italic mt-3 text-right">* Bài mẫu chỉ mang tính tham khảo. Band score thực tế phụ thuộc vào nhiều yếu tố.</p>
                                @else
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-10 text-center">
                                        <span class="text-3xl block mb-3">✨</span>
                                        <p class="text-slate-500 text-sm">Bài mẫu Band 8.0+ chưa được tạo cho Task {{ $taskNum }}.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- TAB: YOUR SUBMISSION --}}
                    <div x-show="writingTab === 'submission'" x-cloak>
                        @foreach($answers as $taskAns)
                            @php
                                $q = $taskAns->question;
                                $taskNum = $q->question_number ?? 1;
                                $essay = $taskAns->user_answer ?? '';
                                $wordCount = str_word_count($essay);
                                $minWords = $taskNum === 1 ? 150 : 250;
                            @endphp
                            <div x-show="activeWritingTask === {{ $taskNum }}" x-cloak>
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="text-sm font-black text-slate-900">Task {{ $taskNum }} – Bài làm của bạn</span>
                                    <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-mono font-bold border
                                        {{ $wordCount >= $minWords ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                                        {{ $wordCount }} từ / {{ $minWords }}+ yêu cầu
                                    </span>
                                </div>
                                @if($essay)
                                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 text-sm text-slate-700 leading-8 font-sans whitespace-pre-line tracking-wide max-h-[600px] overflow-y-auto custom-scroll">
                                        {{ $essay }}
                                    </div>
                                @else
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-10 text-center">
                                        <span class="text-3xl block mb-3">📝</span>
                                        <p class="text-slate-500 text-sm">Bạn chưa nộp bài viết cho Task {{ $taskNum }}.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>

            {{-- Writing Task Prompt Recap --}}
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
                <h2 class="text-base font-black text-slate-900 mb-5 flex items-center gap-2">
                    <span>📋</span> Đề bài Writing (Task Prompts)
                </h2>
                <div class="space-y-4">
                    @foreach($groups as $idx => $taskGroup)
                        @php
                            $q = $taskGroup->questions->first();
                            $taskNum = $q?->question_number ?? ($idx + 1);
                            $minWords = $taskNum === 1 ? 150 : 250;
                        @endphp
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-black text-blue-700">Writing Task {{ $taskNum }}</h3>
                                <span class="text-xs px-2.5 py-0.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 font-bold">
                                    Min {{ $minWords }} words • {{ $taskNum === 1 ? '~20 phút' : '~40 phút' }}
                                </span>
                            </div>
                            @if($taskGroup->instruction)
                                <div class="text-xs text-slate-500 italic">{{ $taskGroup->instruction }}</div>
                            @endif
                            <p class="text-sm text-slate-700 leading-relaxed">{{ $q?->prompt }}</p>
                            @if($taskGroup->image_url)
                                <div class="border border-slate-200 rounded-xl overflow-hidden p-2 bg-white">
                                    <img src="{{ $taskGroup->image_url }}" alt="Task {{ $taskNum }} Chart" class="max-h-64 mx-auto object-contain rounded-lg">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- READING & LISTENING: DETAILED Q&A REVIEW --}}
        {{-- ===================================================== --}}
        @if(!$isWriting)
            <div class="space-y-6">
                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2 mb-4">
                    <span>🔍</span> Giải thích chi tiết & trích dẫn từng câu hỏi
                </h2>

                @foreach($answers as $ans)
                    @php
                        $q = $ans->question;
                        $isCorrect = (bool) $ans->is_correct;
                        $partNum = ceil($q->question_number / 10);
                    @endphp
                    <div class="border rounded-2xl p-6 transition-all shadow-sm {{ $isCorrect ? 'bg-emerald-50/40 border-emerald-200' : 'bg-red-50/40 border-red-200' }}"
                         x-show="filter === 'all' || (filter === 'correct' && {{ $isCorrect ? 'true' : 'false' }}) || (filter === 'incorrect' && {{ !$isCorrect ? 'true' : 'false' }})"
                         x-cloak>

                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $isCorrect ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                                    {{ $q->question_number }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded text-[11px] font-bold {{ $isCorrect ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-red-100 text-red-700 border border-red-200' }}">
                                    {{ $isCorrect ? 'Chính xác' : 'Chưa đúng' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 font-mono">{{ $q->questionGroup?->title }}</span>
                                @if($isListening)
                                    <button type="button"
                                            @click="scrollToTranscriptQuestion({{ $partNum }}, {{ $q->question_number }})"
                                            class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-bold transition-all flex items-center gap-1 active:scale-95">
                                        <span>🎧 Xem trong Transcript</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm font-semibold text-slate-900 mb-4 pl-11 leading-loose">
                            @if(preg_match('/\[blank(_\d+)?\]|_{2,}/', $q->prompt))
                                @php
                                    $userAns = $ans->user_answer ?: '(Bỏ trống)';
                                    $badgeColor = $isCorrect
                                        ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                        : 'bg-red-100 text-red-700 border-red-300 line-through';
                                    $replacement = '<span class="inline-block mx-1 px-3 py-0.5 rounded-lg border font-mono font-bold ' . $badgeColor . '">' . e($userAns) . '</span>';
                                    $promptWithAnswer = preg_replace('/\[blank(_\d+)?\]|_{2,}/', $replacement, e($q->prompt));
                                @endphp
                                {!! $promptWithAnswer !!}
                            @else
                                {{ $q->prompt }}
                            @endif
                            @if(!empty($q->options))
                                <div class="mt-3 space-y-1">
                                    @foreach($q->options as $option)
                                        <div>
                                            <span class="font-bold">{{ $option['key'] }}.</span>
                                            {{ $option['text'] }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        </div>

                        <div class="ml-11 grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            <div class="p-3 rounded-xl border {{ $isCorrect ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-700' }} text-xs">
                                <span class="text-slate-500 text-[10px] font-bold block mb-1">Đáp án của bạn:</span>
                                <strong class="text-sm font-mono">{{ $ans->user_answer ?: '(Bỏ trống)' }}</strong>
                            </div>
                            <div class="p-3 rounded-xl border bg-white border-slate-200 text-slate-700 text-xs">
                                <span class="text-slate-500 text-[10px] font-bold block mb-1">Đáp án đúng chuẩn:</span>
                                <strong class="text-sm font-mono text-emerald-600">{{ $q->correct_answer }}</strong>
                            </div>
                        </div>

                        <div class="ml-11 space-y-3">
                            @if($q->quote_reference)
                                <div class="p-3.5 bg-amber-50 border-l-4 border-amber-400 rounded-r-xl text-xs text-slate-700">
                                    <span class="font-bold text-amber-700 block mb-1">
                                        {{ $isListening ? '🎧 Trích dẫn phát biểu trong Audio (Transcript):' : '📖 Trích dẫn trong bài đọc:' }}
                                    </span>
                                    <em class="text-slate-800 font-serif leading-relaxed">"{{ $q->quote_reference }}"</em>
                                </div>
                            @endif
                            @if($q->explanation)
                                <div class="p-3.5 bg-white rounded-xl text-xs text-slate-600 leading-relaxed border border-slate-200">
                                    <span class="font-bold text-blue-600 block mb-1">💡 Lời giải thích &amp; bẫy distractors:</span>
                                    {{ $q->explanation }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

<style>
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: rgba(148,163,184,0.15); }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.35); border-radius: 4px; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
</style>
@endsection
