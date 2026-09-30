<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IELTS Computer-Delivered Test Simulator | {{ $submission->test?->title ?? 'IELTS Examination' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            user-select: text;
        }

        /* Color Contrast Modes */
        body.contrast-standard {
            --bg-main: #f8fafc;
            --bg-header: #ffffff;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
        }
        body.contrast-black-white {
            --bg-main: #000000;
            --bg-header: #111111;
            --bg-card: #18181b;
            --text-main: #ffffff;
            --text-muted: #a1a1aa;
            --border-color: #3f3f46;
        }
        body.contrast-yellow-black {
            --bg-main: #000000;
            --bg-header: #0a0a0a;
            --bg-card: #121212;
            --text-main: #facc15;
            --text-muted: #ca8a04;
            --border-color: #854d0e;
        }

        /* Font Sizes */
        .font-size-standard { font-size: 15px; }
        .font-size-large { font-size: 17px; }
        .font-size-xlarge { font-size: 19px; }

        /* Highlight mark styling - Authentic IELTS Yellow */
        .ielts-highlight {
            background-color: #fde047 !important;
            color: #111827 !important;
            padding: 1px 3px;
            border-radius: 2px;
            cursor: pointer;
            border-bottom: 2px solid #ca8a04;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            transition: background-color 0.15s ease-in-out;
            display: inline;
        }
        .ielts-highlight:hover {
            background-color: #facc15 !important;
        }
        .ielts-highlight[data-note]::after {
            content: ' 📝';
            font-size: 11px;
            vertical-align: super;
            margin-left: 2px;
        }

        /* Contrast overrides for Highlights */
        body.contrast-black-white .ielts-highlight {
            background-color: #ffffff !important;
            color: #000000 !important;
            border-bottom-color: #999999 !important;
        }
        body.contrast-yellow-black .ielts-highlight {
            background-color: #eab308 !important;
            color: #000000 !important;
            border-bottom-color: #ca8a04 !important;
        }

        /* Custom Scrollbars */
        .custom-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(100,116,139,0.4);
            border-radius: 4px;
        }
    </style>
</head>

@php
    $skill = $submission->skill ?? 'reading';
    $isWriting = ($skill === 'writing');
    $isListening = ($skill === 'listening');
    $isReading = ($skill === 'reading');
    $groups = $submission->section->questionGroups;
@endphp

<body class="contrast-standard select-text h-screen overflow-hidden flex flex-col"
      x-data="ieltsSimulator({
          submissionId: '{{ $submission->id }}',
          initialSeconds: {{ $remainingSeconds }},
          totalQuestions: {{ $submission->total_questions }},
          skill: '{{ $skill }}',
          userAnswers: {{ Js::from($userAnswersMap->map(fn($ua) => [
              'answer' => $ua->user_answer,
              'is_flagged' => (bool)$ua->is_flagged_for_review,
              'notes' => $ua->notes,
          ])) }},
          saveUrl: '{{ route('ielts.exam.save', $submission->id) }}',
          submitUrl: '{{ route('ielts.exam.submit', $submission->id) }}',
          resultUrl: '{{ route('ielts.exam.result', $submission->id) }}',
      })"
      :class="[contrastClass, fontSizeClass]"
      @click="handleGlobalClick($event)">

    {{-- ========================================================================= --}}
    {{-- 1. FIXED HEADER BAR (IDP / BC STANDARD) --}}
    {{-- ========================================================================= --}}
    <header class="bg-white border-b-2 border-slate-300 px-4 py-2 flex items-center justify-between shadow-sm z-30 flex-shrink-0"
            style="background-color: var(--bg-header); color: var(--text-main); border-color: var(--border-color);">
        {{-- Left: Candidate Details --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full text-white flex items-center justify-center font-black text-sm uppercase shadow-sm flex-shrink-0
                @if($isListening) bg-blue-600 @elseif($isWriting) bg-amber-600 @else bg-emerald-600 @endif">
                {{ substr(Auth::user()->name ?? 'C', 0, 1) }}
            </div>
            <div class="text-xs">
                <div class="font-bold flex items-center gap-1.5" style="color: var(--text-main)">
                    <span>{{ Auth::user()->name ?? 'Candidate Guest' }}</span>
                    <span class="px-1.5 py-0.2 bg-slate-200 text-slate-700 rounded text-[10px] font-mono">
                        {{ $submission->metadata['candidate_number'] ?? 'IDP-012345' }}
                    </span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase tracking-wider
                        @if($isListening) bg-blue-100 text-blue-700 @elseif($isWriting) bg-amber-100 text-amber-700 @else bg-emerald-100 text-emerald-700 @endif">
                        {{ ucfirst($skill) }}
                    </span>
                </div>
                <div class="text-[11px] font-medium" style="color: var(--text-muted)">
                    {{ $submission->test?->title ?? ($submission->section?->title ?? 'IELTS Examination') }} • Room: {{ $submission->metadata['room_number'] ?? 'VN-008' }}
                </div>
            </div>
        </div>

        {{-- Center: Countdown Timer Chuẩn IDP --}}
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg border-2 font-mono shadow-sm transition-all"
                 :class="{
                     'bg-red-50 border-red-500 text-red-600 animate-pulse font-black shadow-red-200': isFiveMinutesOrLess,
                     'bg-amber-50 border-amber-500 text-amber-700 font-bold': isTenMinutesOrLess && !isFiveMinutesOrLess,
                     'bg-slate-100 border-slate-300 text-slate-900 font-bold': !isTenMinutesOrLess
                 }"
                 style="background-color: var(--bg-card); border-color: isTenMinutesOrLess ? '#ef4444' : 'var(--border-color)';">
                <svg class="w-4 h-4 flex-shrink-0" :class="isTenMinutesOrLess ? 'text-red-600' : 'text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <div class="text-sm tracking-wide cursor-pointer select-none"
                     x-show="showTimer"
                     @click="toggleTimerFormat()"
                     title="Bấm để đổi định dạng MM:SS / Số phút còn lại">
                    <span x-text="formattedTime"></span>
                </div>

                <div class="text-xs font-semibold text-slate-500" x-show="!showTimer">
                    Time remaining
                </div>

                {{-- Nút Hide/Show theo chuẩn IDP (Không được ẩn trong 10 phút cuối) --}}
                <template x-if="!isTenMinutesOrLess">
                    <button type="button"
                            @click="showTimer = !showTimer"
                            class="text-[11px] underline text-slate-500 hover:text-slate-800 ml-1 font-sans font-medium">
                        <span x-text="showTimer ? 'Hide' : 'Show'">Hide</span>
                    </button>
                </template>
                <template x-if="isTenMinutesOrLess">
                    <span class="text-[10px] text-red-600 font-semibold bg-red-100/80 px-1.5 py-0.5 rounded border border-red-200" title="Không thể ẩn đồng hồ trong 10 phút cuối">
                        Cố định (≤10m)
                    </span>
                </template>
            </div>

            {{-- Autosave indicator --}}
            <div class="hidden sm:flex items-center gap-1 text-[11px] text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full" :class="saving ? 'bg-amber-400 animate-ping' : 'bg-emerald-500'"></span>
                <span x-text="saving ? 'Đang lưu...' : 'Đã tự lưu'">Đã tự lưu</span>
            </div>
        </div>

        {{-- Right: Exam Tools (Contrast, Font size, Fullscreen, Clear Highlights, Help) --}}
        <div class="flex items-center gap-2 text-xs">
            {{-- Contrast Menu --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 flex items-center gap-1 font-medium text-slate-700 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                    <span>🎨 Tương phản</span>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-xl py-1 z-50 text-slate-800 text-xs">
                    <button @click="contrastClass = 'contrast-standard'; open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 flex items-center justify-between">
                        <span>Tiêu chuẩn (Trắng)</span>
                        <span class="w-3 h-3 rounded-full bg-slate-100 border border-slate-300"></span>
                    </button>
                    <button @click="contrastClass = 'contrast-black-white'; open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 flex items-center justify-between">
                        <span>Đen / Trắng (Dark)</span>
                        <span class="w-3 h-3 rounded-full bg-black border border-slate-600"></span>
                    </button>
                    <button @click="contrastClass = 'contrast-yellow-black'; open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 flex items-center justify-between">
                        <span>Vàng / Đen</span>
                        <span class="w-3 h-3 rounded-full bg-yellow-400 border border-yellow-600"></span>
                    </button>
                </div>
            </div>

            {{-- Font Size Menu --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 flex items-center gap-1 font-medium text-slate-700 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                    <span>🔤 Cỡ chữ</span>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-1 w-36 bg-white border border-slate-200 rounded-lg shadow-xl py-1 z-50 text-slate-800 text-xs">
                    <button @click="fontSizeClass = 'font-size-standard'; open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-100">Tiêu chuẩn (100%)</button>
                    <button @click="fontSizeClass = 'font-size-large'; open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 font-medium">Lớn (115%)</button>
                    <button @click="fontSizeClass = 'font-size-xlarge'; open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 font-bold">Rất lớn (130%)</button>
                </div>
            </div>

            {{-- Nút Xóa tất cả Highlight (chỉ hiển thị khi làm Reading/Listening) --}}
            @if(!$isWriting)
                <button @click="clearAllHighlights()" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 font-medium text-slate-700 shadow-sm" title="Xóa toàn bộ các đoạn tô sáng" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                    <span>🧹 Xóa Highlight</span>
                </button>
            @endif

            {{-- Fullscreen Toggle --}}
            <button @click="toggleFullscreen" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 bg-white hover:bg-slate-50 font-medium text-slate-700 shadow-sm" title="Toàn màn hình" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                <span x-text="isFullscreen ? '⛶ Thu nhỏ' : '⛶ Toàn màn hình'">⛶ Toàn màn hình</span>
            </button>

            {{-- Help Modal Trigger --}}
            <button @click="helpModal = true" type="button" class="w-7 h-7 rounded-full border border-slate-300 flex items-center justify-center font-bold text-slate-600 hover:bg-slate-100" title="Hướng dẫn làm bài">
                ?
            </button>
        </div>
    </header>

    {{-- ========================================================================= --}}
    {{-- LISTENING AUDIO STREAM BAR (NẾU ĐANG THI LISTENING) --}}
    {{-- ========================================================================= --}}
    @if($isListening)
        {{-- Autoplay Blocked Notification Banner --}}
        <div x-show="autoplayBlocked"
             x-cloak
             class="bg-amber-500 text-slate-950 px-4 py-2 flex items-center justify-between text-xs font-bold shadow-md z-40 border-b border-amber-600">
            <div class="flex items-center gap-2">
                <span class="text-base animate-bounce">📢</span>
                <span>Trình duyệt yêu cầu xác nhận trước khi phát âm thanh bài thi Listening. Bấm nút bên cạnh để bắt đầu nghe:</span>
            </div>
            <button type="button"
                    @click="startAudioStream()"
                    class="px-4 py-1.5 bg-slate-950 hover:bg-slate-800 text-white rounded-lg font-black transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                <span>▶ Bắt đầu phát Audio</span>
            </button>
        </div>

        {{-- Answer Check Phase Announcement Banner --}}
        <div x-show="isAnswerCheckPhase"
             x-cloak
             class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-1.5 flex items-center justify-between text-xs font-bold shadow z-40">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                <span>⏱️ GIAI ĐOẠN KIỂM TRA ĐÁP ÁN: Audio đã kết thúc. Bạn có 2 phút để rà soát toàn bộ bài thi!</span>
            </div>
            <span class="bg-blue-900/80 px-2 py-0.5 rounded text-[11px] font-mono" x-text="formattedTime">02:00 left</span>
        </div>

        {{-- Main Audio Control Bar --}}
        <div class="bg-blue-50 text-slate-800 px-4 py-2 border-b border-blue-100 flex items-center justify-between text-xs flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-2.5 py-1 rounded bg-blue-100 text-blue-700 font-bold border border-blue-200">
                    <span class="flex items-center gap-0.5">
                        <span class="w-1 h-3 bg-blue-400 rounded-full" :class="audioPlaying ? 'animate-pulse' : ''"></span>
                        <span class="w-1 h-4 bg-blue-300 rounded-full" :class="audioPlaying ? 'animate-pulse' : ''"></span>
                        <span class="w-1 h-2 bg-blue-400 rounded-full" :class="audioPlaying ? 'animate-pulse' : ''"></span>
                    </span>
                    <span>🎧 IELTS Listening Audio (Phát 1 lần duy nhất)</span>
                </div>
                <span class="text-slate-600 text-[11px] font-mono" x-text="`${audioCurrentTime} / ${audioDuration}`">00:00 / 30:00</span>
            </div>

            {{-- Visual Progress Bar (Read-only, strictly anti-scrubbing) --}}
            <div class="hidden sm:block flex-grow mx-6 bg-blue-100 rounded-full h-2 overflow-hidden border border-blue-200 pointer-events-none"
                 title="Thanh tiến độ nghe (Không thể tua lại theo quy chế thi IELTS)">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-full rounded-full transition-all duration-300 shadow-sm"
                     :style="`width: ${audioProgress}%`"></div>
            </div>

            {{-- Precision Volume Control Slider --}}
            <div class="flex items-center gap-2.5">
                <button type="button"
                        @click="toggleAudioMute()"
                        class="p-1 rounded text-slate-500 hover:text-slate-900 transition-colors"
                        :title="audioMuted ? 'Bật âm thanh' : 'Tắt tiếng'">
                    <span x-text="audioMuted ? '🔇' : (audioVolume > 50 ? '🔊' : '🔉')">🔊</span>
                </button>
                <input type="range"
                       min="0"
                       max="100"
                       x-model="audioVolume"
                       @input="setAudioVolume($event.target.value)"
                       class="w-20 sm:w-28 accent-blue-500 cursor-pointer h-1.5 bg-blue-200 rounded-lg">
                <span class="text-[11px] font-mono text-slate-600 w-8 text-right" x-text="audioMuted ? '0%' : `${audioVolume}%`">80%</span>
            </div>

            @php
                $firstAudio = $groups->firstWhere('audio_url', '!=', null)?->audio_url
                    ?? 'https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg';
            @endphp
            <audio x-ref="audioPlayer"
                   @timeupdate="updateAudioTime()"
                   @ended="onAudioEnded()"
                   src="{{ $firstAudio }}"
                   preload="auto"></audio>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- 2. NAVIGATION TABS (PARTS / PASSAGES / WRITING TASKS) --}}
    {{-- ========================================================================= --}}
    <nav class="bg-slate-200 border-b border-slate-300 px-4 py-1.5 flex items-center gap-2 flex-shrink-0" style="background-color: var(--bg-header); border-color: var(--border-color);">
        @if($isWriting)
            @foreach($groups as $idx => $taskGroup)
                @php
                    $q = $taskGroup->questions->first();
                    $taskNum = $q?->question_number ?? ($idx + 1);
                @endphp
                <button type="button"
                        @click="activeTaskId = {{ $q?->id ?? 1 }}; currentTaskNumber = {{ $taskNum }}"
                        class="px-4 py-1.5 rounded-t text-xs font-bold transition-all border-b-2 flex items-center gap-2"
                        :class="currentTaskNumber === {{ $taskNum }} ? 'bg-white text-amber-600 border-amber-600 shadow-sm' : 'text-slate-600 border-transparent hover:text-slate-900'">
                    <span>Task {{ $taskNum }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-mono">
                        {{ $taskNum === 1 ? '≥ 150 từ' : '≥ 250 từ' }}
                    </span>
                </button>
            @endforeach
        @elseif($isListening)
            {{-- 4 Official IELTS Listening Parts --}}
            @php
                $listeningPartDefs = [
                    1 => ['title' => 'Part 1', 'desc' => 'Notes Completion (1–10)'],
                    2 => ['title' => 'Part 2', 'desc' => 'Facilities & Map (11–20)'],
                    3 => ['title' => 'Part 3', 'desc' => 'Academic Seminar (21–30)'],
                    4 => ['title' => 'Part 4', 'desc' => 'Lecture Notes (31–40)'],
                ];
            @endphp
            @foreach($listeningPartDefs as $partIdx => $def)
                <button type="button"
                        @click="activeListeningPart = {{ $partIdx }}; currentQuestionNumber = {{ ($partIdx - 1) * 10 + 1 }}; scrollToQuestion(currentQuestionNumber)"
                        class="px-4 py-1.5 rounded-t text-xs font-bold transition-all border-b-2 flex items-center gap-2"
                        :class="activeListeningPart === {{ $partIdx }} ? 'bg-white text-blue-600 border-blue-600 shadow-sm' : 'text-slate-600 border-transparent hover:text-slate-900'">
                    <span>{{ $def['title'] }}</span>
                    <span class="text-[10px] text-slate-500 font-normal hidden md:inline">({{ $def['desc'] }})</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded font-mono font-bold"
                          :class="getPartAnsweredCount({{ $partIdx }}) === 10 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                          x-text="`${getPartAnsweredCount({{ $partIdx }})}/10`">
                        0/10
                    </span>
                </button>
            @endforeach
        @else
            {{-- Reading Passages --}}
            @php $passages = $groups->whereNotNull('passage_content'); @endphp
            @foreach($passages as $idx => $passageGroup)
                <button type="button"
                        @click="activePassageId = {{ $passageGroup->id }}; currentQuestionNumber = {{ $passageGroup->questions->first()?->question_number ?? 1 }}; scrollToQuestion(currentQuestionNumber)"
                        class="px-4 py-1.5 rounded-t text-xs font-bold transition-all border-b-2"
                        :class="activePassageId === {{ $passageGroup->id }} ? 'bg-white text-blue-600 border-blue-600 shadow-sm' : 'text-slate-600 border-transparent hover:text-slate-900'">
                    Passage {{ $loop->iteration }}
                </button>
            @endforeach
        @endif
    </nav>

    {{-- ========================================================================= --}}
    {{-- 3. MAIN WORKSPACE AREA --}}
    {{-- ========================================================================= --}}

    {{-- CASE A: WRITING SIMULATOR (SPLIT SCREEN + WORD COUNTER) --}}
    @if($isWriting)
        <div class="flex-grow flex flex-col md:flex-row overflow-hidden relative" style="background-color: var(--bg-main);">
            {{-- Left: Task Instructions & Prompt --}}
            <div class="w-full md:w-1/2 h-1/2 md:h-full overflow-y-auto custom-scroll p-6 md:p-8 border-r-2 border-slate-300"
                 style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                @foreach($groups as $idx => $taskGroup)
                    @php
                        $q = $taskGroup->questions->first();
                        $taskNum = $q?->question_number ?? ($idx + 1);
                        $minWords = ($taskNum === 1 ? 150 : 250);
                    @endphp
                    <div x-show="currentTaskNumber === {{ $taskNum }}" x-cloak class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <h2 class="text-base font-black text-amber-600 uppercase tracking-wide">
                                Writing Task {{ $taskNum }}
                            </h2>
                            <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-lg text-xs font-bold border border-amber-200">
                                Yêu cầu: Tối thiểu {{ $minWords }} từ
                            </span>
                        </div>

                        @if($taskGroup->instruction)
                            <div class="bg-amber-50/70 border-l-4 border-amber-500 p-3 rounded-r-lg text-xs text-amber-900 font-medium">
                                {{ $taskGroup->instruction }}
                            </div>
                        @endif

                        <div class="text-sm font-semibold leading-relaxed p-4 rounded-xl border border-slate-200 bg-slate-50/60" style="color: var(--text-main);">
                            {{ $q?->prompt }}
                        </div>

                        @if($taskGroup->image_url)
                            <div class="mt-4 border border-slate-200 rounded-xl overflow-hidden p-2 bg-white">
                                <img src="{{ $taskGroup->image_url }}" alt="Task Chart" class="max-h-80 mx-auto object-contain">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Right: Plain Text Area Editor & Real-time Word Counter --}}
            <div class="w-full md:w-1/2 h-1/2 md:h-full p-6 md:p-8 flex flex-col justify-between"
                 style="background-color: var(--bg-main);">
                @foreach($groups as $idx => $taskGroup)
                    @php
                        $q = $taskGroup->questions->first();
                        $qId = $q?->id ?? 1;
                        $taskNum = $q?->question_number ?? ($idx + 1);
                        $minWords = ($taskNum === 1 ? 150 : 250);
                    @endphp
                    <div x-show="currentTaskNumber === {{ $taskNum }}" x-cloak class="flex-grow flex flex-col h-full">
                        <div class="flex items-center justify-between mb-2 text-xs">
                            <span class="font-bold text-slate-500">Trình soạn thảo văn bản thô (Plain Text)</span>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-md text-xs font-mono font-bold border transition-all"
                                      :class="getWordCount(answers['{{ $qId }}']) >= {{ $minWords }}
                                          ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                          : (getWordCount(answers['{{ $qId }}']) >= Math.round({{ $minWords }} * 0.7)
                                              ? 'bg-amber-50 text-amber-700 border-amber-300'
                                              : 'bg-slate-100 text-slate-700 border-slate-300')">
                                    <span x-text="getWordCount(answers['{{ $qId }}'])">0</span> / {{ $minWords }} từ
                                </span>
                            </div>
                        </div>

                        {{-- Visual Word Count Progress Bar with Milestones --}}
                        <div class="mb-3 space-y-1.5">
                            <div class="relative w-full rounded-full h-2.5 overflow-hidden" style="background-color: var(--border-color);">
                                <div class="absolute h-full rounded-full transition-all duration-500"
                                     :class="getWordCount(answers['{{ $qId }}']) >= {{ $minWords }}
                                         ? 'bg-gradient-to-r from-emerald-500 to-teal-400'
                                         : 'bg-gradient-to-r from-amber-400 to-orange-400'"
                                     :style="'width: ' + Math.min(100, Math.round((getWordCount(answers[\'{{ $qId }}\']) / {{ $minWords }}) * 100)) + '%'">
                                </div>
                                <div class="absolute top-0 h-full w-px pointer-events-none" style="left: 50%; background: rgba(255,255,255,0.5);"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] font-semibold" style="color: var(--text-muted);">
                                <span>0</span>
                                <span :class="getWordCount(answers['{{ $qId }}']) >= {{ round($minWords * 0.5) }} ? 'text-amber-600 font-black' : ''">{{ round($minWords * 0.5) }}</span>
                                <span :class="getWordCount(answers['{{ $qId }}']) >= {{ $minWords }} ? 'text-emerald-600 font-black' : ''">{{ $minWords }} ✓</span>
                                <span :class="getWordCount(answers['{{ $qId }}']) >= {{ round($minWords * 1.4) }} ? 'text-blue-600 font-black' : ''">{{ round($minWords * 1.4) }}+</span>
                            </div>
                        </div>

                        <textarea x-model="answers['{{ $qId }}']"
                                  @input.debounce.300ms="saveAnswer({{ $qId }}, answers['{{ $qId }}'])"
                                  @keydown.tab.prevent="
                                      const s = $event.target.selectionStart, e2 = $event.target.selectionEnd, v = $event.target.value;
                                      $event.target.value = v.substring(0,s) + '    ' + v.substring(e2);
                                      $event.target.selectionStart = $event.target.selectionEnd = s + 4;
                                      answers['{{ $qId }}'] = $event.target.value;
                                  "
                                  spellcheck="false"
                                  autocorrect="off"
                                  autocapitalize="off"
                                  placeholder="Type your response here..."
                                  class="w-full flex-grow p-4 text-sm font-sans leading-relaxed rounded-2xl border-2 focus:ring-0 outline-none resize-none transition-all"
                                  :class="getWordCount(answers['{{ $qId }}']) >= {{ $minWords }}
                                      ? 'border-emerald-400 focus:border-emerald-500 shadow-inner shadow-emerald-50'
                                      : 'border-slate-300 focus:border-amber-500'"
                                  style="background-color: var(--bg-card); color: var(--text-main); min-height: 320px;"></textarea>

                        <div class="mt-2 flex items-center justify-between text-[11px]" style="color: var(--text-muted);">
                            <span>
                                <span x-show="getWordCount(answers['{{ $qId }}']) < {{ $minWords }}" x-cloak class="text-amber-600 font-semibold">
                                    ⚠ Cần thêm <strong x-text="{{ $minWords }} - getWordCount(answers['{{ $qId }}'])">0</strong> từ nữa
                                </span>
                                <span x-show="getWordCount(answers['{{ $qId }}']) >= {{ $minWords }}" x-cloak class="text-emerald-600 font-bold">
                                    ✅ Đạt yêu cầu tối thiểu {{ $minWords }} từ
                                </span>
                            </span>
                            <span class="font-mono text-[10px]">Tab = indent 4 spaces</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    {{-- CASE B: LISTENING WORKSPACE (AUTHENTIC IDP/BC FORMAT: FORM COMPLETION, MAP LABELING, LECTURE NOTES) --}}
    @elseif($isListening)
        <div class="flex-grow overflow-y-auto custom-scroll p-4 md:p-8"
             style="background-color: var(--bg-main);"
             @mouseup="handleTextSelection($event)">

            <div class="max-w-5xl mx-auto space-y-6">

                {{-- PART 1: FORM COMPLETION --}}
                <div x-show="activeListeningPart === 1" x-cloak class="space-y-6">
                    @php $p1Group = $groups->firstWhere('order', 1) ?? $groups->first(); @endphp
                    @if($p1Group)
                        {{-- Instruction Box --}}
                        <div class="bg-blue-50/80 border-l-4 border-blue-600 p-4 rounded-r-2xl shadow-sm text-xs text-blue-950 font-medium">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-extrabold uppercase text-[11px] text-blue-800 tracking-wider">IELTS Listening • Part 1: Form & Notes Completion</span>
                                <span class="px-2 py-0.5 rounded bg-blue-200 text-blue-800 font-bold text-[10px]">10 câu hỏi (1–10)</span>
                            </div>
                            <p class="font-bold text-sm text-blue-900 mt-1">{{ $p1Group->instruction ?? 'Questions 1–10: Complete the notes below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.' }}</p>
                        </div>

                        {{-- Authentic Structured Form Card (Metro Bike Hire) --}}
                        <div class="bg-white rounded-3xl border-2 border-slate-300 p-6 md:p-10 shadow-sm"
                             style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">

                            {{-- Form Header Badge --}}
                            <div class="border-b-2 border-slate-200 pb-5 mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="text-[11px] font-black uppercase tracking-widest text-blue-600 mb-1">Official Enquiry Record</div>
                                    <h2 class="text-xl md:text-2xl font-black text-slate-900" style="color: var(--text-main);">
                                        METRO BIKE HIRE – CUSTOMER ENQUIRY & RENTAL
                                    </h2>
                                </div>
                                <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 text-xs font-mono font-bold border border-slate-300 self-start sm:self-auto">
                                    Ref: MBH-2026-ENQ
                                </span>
                            </div>

                            {{-- Form Content Divided into 3 Sections --}}
                            <div class="space-y-8">
                                {{-- Section A: Customer Details --}}
                                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4" style="background-color: var(--bg-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <span>👤</span> 1. Customer Information
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p1Group->questions->whereBetween('question_number', [1, 3]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-100/50 transition-colors"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}">
                                                <div class="flex items-center gap-3 flex-grow">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <div class="text-sm font-medium leading-relaxed flex-grow" style="color: var(--text-main);">
                                                        @php
                                                            $inputHtml = '<span class="inline-flex items-center mx-1.5 align-middle"><input type="text" tabindex="' . $q->question_number . '" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1.5 text-sm font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none w-44 sm:w-56 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                            $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                        @endphp
                                                        {!! $renderedPrompt !!}
                                                    </div>
                                                </div>
                                                <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1.5 rounded hover:bg-slate-200/60 text-slate-400" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''" title="Đánh dấu câu hỏi cần xem lại">
                                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Section B: Hire Requirements --}}
                                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4" style="background-color: var(--bg-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <span>🚲</span> 2. Bicycle Hire Requirements
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p1Group->questions->whereBetween('question_number', [4, 7]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-100/50 transition-colors"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}">
                                                <div class="flex items-center gap-3 flex-grow">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <div class="text-sm font-medium leading-relaxed flex-grow" style="color: var(--text-main);">
                                                        @php
                                                            $inputHtml = '<span class="inline-flex items-center mx-1.5 align-middle"><input type="text" tabindex="' . $q->question_number . '" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1.5 text-sm font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none w-44 sm:w-56 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                            $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                        @endphp
                                                        {!! $renderedPrompt !!}
                                                    </div>
                                                </div>
                                                <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1.5 rounded hover:bg-slate-200/60 text-slate-400" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''" title="Đánh dấu câu hỏi cần xem lại">
                                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Section C: Collection & Payment Details --}}
                                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4" style="background-color: var(--bg-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <span>💳</span> 3. Collection & Payment Terms
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p1Group->questions->whereBetween('question_number', [8, 10]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-100/50 transition-colors"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}">
                                                <div class="flex items-center gap-3 flex-grow">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <div class="text-sm font-medium leading-relaxed flex-grow" style="color: var(--text-main);">
                                                        @php
                                                            $inputHtml = '<span class="inline-flex items-center mx-1.5 align-middle"><input type="text" tabindex="' . $q->question_number . '" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1.5 text-sm font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none w-44 sm:w-56 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                            $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                        @endphp
                                                        {!! $renderedPrompt !!}
                                                    </div>
                                                </div>
                                                <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1.5 rounded hover:bg-slate-200/60 text-slate-400" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''" title="Đánh dấu câu hỏi cần xem lại">
                                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- PART 2: FACILITIES & MAP LABELING (SPLIT SCREEN) --}}
                <div x-show="activeListeningPart === 2" x-cloak class="space-y-6">
                    @php $p2Group = $groups->firstWhere('order', 2) ?? $groups->skip(1)->first(); @endphp
                    @if($p2Group)
                        {{-- Instruction Box --}}
                        <div class="bg-blue-50/80 border-l-4 border-blue-600 p-4 rounded-r-2xl shadow-sm text-xs text-blue-950 font-medium">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-extrabold uppercase text-[11px] text-blue-800 tracking-wider">IELTS Listening • Part 2: Community Centre Facilities & Map</span>
                                <span class="px-2 py-0.5 rounded bg-blue-200 text-blue-800 font-bold text-[10px]">10 câu hỏi (11–20)</span>
                            </div>
                            <p class="font-bold text-sm text-blue-900 mt-1">Questions 11–15: Choose the correct letter, A, B or C. Questions 16–20: Label the map below (Write A–E).</p>
                        </div>

                        <div class="flex flex-col lg:flex-row gap-6">
                            {{-- Left Column: Map SVG Floor Plan --}}
                            <div class="w-full lg:w-1/2 bg-white rounded-3xl border-2 border-slate-300 p-5 shadow-sm space-y-4"
                                 style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                        <span>🗺️</span> Westbridge Community Centre Plan
                                    </h3>
                                    <span class="text-[11px] text-slate-400 font-mono">Questions 16–20</span>
                                </div>

                                {{-- Floor Plan SVG --}}
                                <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-sm bg-slate-50">
                                    <svg viewBox="0 0 700 500" class="w-full h-auto select-none" xmlns="http://www.w3.org/2000/svg">
                                        <!-- Building border -->
                                        <rect x="25" y="25" width="650" height="450" rx="16" fill="#f8fafc" stroke="#94a3b8" stroke-width="3" />
                                        <text x="350" y="55" text-anchor="middle" fill="#475569" font-size="15" font-weight="bold" letter-spacing="1">WESTBRIDGE COMMUNITY CENTRE — FLOOR PLAN</text>

                                        <!-- Sports Hall (Top Left) -->
                                        <rect x="50" y="80" width="180" height="140" rx="10" fill="#cbd5e1" stroke="#64748b" stroke-width="2" />
                                        <text x="140" y="155" text-anchor="middle" fill="#1e293b" font-size="15" font-weight="bold">Sports Hall</text>

                                        <!-- Underground Garage Arrow -->
                                        <text x="140" y="245" text-anchor="middle" fill="#64748b" font-size="11">⬇ Basement Parking</text>

                                        <!-- Courtyard (Center) -->
                                        <rect x="250" y="160" width="200" height="150" rx="10" fill="#ffffff" stroke="#cbd5e1" stroke-dasharray="5,5" />
                                        <text x="350" y="240" text-anchor="middle" fill="#64748b" font-size="14" font-weight="bold">Central Courtyard</text>

                                        <!-- Location A: Main Reception (Bottom Left) -->
                                        <rect x="50" y="270" width="180" height="150" rx="10" fill="#dbeafe" stroke="#3b82f6" stroke-width="2.5" />
                                        <circle cx="140" cy="330" r="24" fill="#ef4444" stroke="#ffffff" stroke-width="2" />
                                        <text x="140" y="338" text-anchor="middle" fill="#ffffff" font-size="20" font-weight="black">A</text>
                                        <text x="140" y="380" text-anchor="middle" fill="#1d4ed8" font-size="12" font-weight="bold">Location A</text>

                                        <!-- Location B: Café (Bottom Right) -->
                                        <rect x="470" y="270" width="180" height="90" rx="10" fill="#dbeafe" stroke="#3b82f6" stroke-width="2.5" />
                                        <circle cx="560" cy="305" r="22" fill="#ef4444" stroke="#ffffff" stroke-width="2" />
                                        <text x="560" y="313" text-anchor="middle" fill="#ffffff" font-size="18" font-weight="black">B</text>
                                        <text x="560" y="345" text-anchor="middle" fill="#1d4ed8" font-size="12" font-weight="bold">Location B</text>

                                        <!-- Location C: Playroom (Far Right Bottom) -->
                                        <rect x="470" y="380" width="180" height="75" rx="10" fill="#dbeafe" stroke="#3b82f6" stroke-width="2.5" />
                                        <circle cx="560" cy="415" r="20" fill="#ef4444" stroke="#ffffff" stroke-width="2" />
                                        <text x="560" y="422" text-anchor="middle" fill="#ffffff" font-size="16" font-weight="black">C</text>
                                        <text x="560" y="445" text-anchor="middle" fill="#1d4ed8" font-size="11" font-weight="bold">Location C</text>

                                        <!-- Location D: Reading Room (Top Center) -->
                                        <rect x="250" y="80" width="200" height="65" rx="10" fill="#dbeafe" stroke="#3b82f6" stroke-width="2.5" />
                                        <circle cx="350" cy="112" r="20" fill="#ef4444" stroke="#ffffff" stroke-width="2" />
                                        <text x="350" y="119" text-anchor="middle" fill="#ffffff" font-size="16" font-weight="black">D</text>
                                        <text x="350" y="137" text-anchor="middle" fill="#1d4ed8" font-size="11" font-weight="bold">Location D</text>

                                        <!-- Location E: Terrace (Top Right) -->
                                        <rect x="470" y="80" width="180" height="170" rx="10" fill="#dcfce7" stroke="#22c55e" stroke-width="2.5" />
                                        <circle cx="560" cy="155" r="24" fill="#ef4444" stroke="#ffffff" stroke-width="2" />
                                        <text x="560" y="163" text-anchor="middle" fill="#ffffff" font-size="20" font-weight="black">E</text>
                                        <text x="560" y="205" text-anchor="middle" fill="#15803d" font-size="12" font-weight="bold">Location E</text>

                                        <!-- Main Entrance at Bottom Center -->
                                        <path d="M 310 475 L 390 475" stroke="#f59e0b" stroke-width="6" stroke-linecap="round" />
                                        <polygon points="350,445 338,465 362,465" fill="#f59e0b" />
                                        <text x="350" y="493" text-anchor="middle" fill="#b45309" font-size="12" font-weight="black">MAIN ENTRANCE (YOU ARE HERE)</text>
                                    </svg>
                                </div>
                            </div>

                            {{-- Right Column: Questions 11–15 & Questions 16–20 --}}
                            <div class="w-full lg:w-1/2 space-y-6">
                                {{-- Subgroup 1: Multiple Choice Questions 11–15 --}}
                                <div class="bg-white rounded-3xl border-2 border-slate-300 p-6 shadow-sm space-y-5"
                                     style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-2">
                                        Questions 11–15: Choose the correct letter, A, B or C
                                    </h3>
                                    <div class="space-y-5">
                                        @foreach($p2Group->questions->whereBetween('question_number', [11, 15]) as $q)
                                            <div class="p-3 rounded-2xl border border-slate-200 space-y-3"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}"
                                                 @click="setCurrentQuestion({{ $q->question_number }})">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex items-start gap-2.5">
                                                        <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 mt-0.5"
                                                              :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                            {{ $q->question_number }}
                                                        </span>
                                                        <p class="text-sm font-semibold leading-relaxed" style="color: var(--text-main);">{{ $q->prompt }}</p>
                                                    </div>
                                                    <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1 rounded text-slate-400 hover:bg-slate-100" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''">
                                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                    </button>
                                                </div>
                                                <div class="ml-9 space-y-2">
                                                    @foreach($q->options ?? [] as $opt)
                                                        @php
                                                            $optKey = $opt['key'] ?? '';
                                                            $optText = $opt['text'] ?? '';
                                                        @endphp
                                                        <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100/70 transition-colors"
                                                               :class="answers['{{ $q->id }}'] === '{{ $optKey }}' ? 'border-blue-600 bg-blue-50/40 font-semibold' : ''">
                                                            <input type="radio" name="listening_q_{{ $q->id }}" value="{{ $optKey }}" x-model="answers['{{ $q->id }}']" @change="saveAnswer({{ $q->id }}, '{{ $optKey }}')" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300">
                                                            <span class="text-xs"><strong>{{ $optKey }}.</strong> {{ $optText }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Subgroup 2: Map Labeling Questions 16–20 --}}
                                <div class="bg-white rounded-3xl border-2 border-slate-300 p-6 shadow-sm space-y-4"
                                     style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-2">
                                        Questions 16–20: Map Locations (Select Letter A–E)
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p2Group->questions->whereBetween('question_number', [16, 20]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-3 rounded-2xl border border-slate-200"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}"
                                                 @click="setCurrentQuestion({{ $q->question_number }})">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <span class="text-sm font-bold" style="color: var(--text-main);">{{ $q->prompt }}</span>
                                                </div>

                                                {{-- Letter Buttons A, B, C, D, E --}}
                                                <div class="flex items-center gap-1.5">
                                                    @foreach(['A', 'B', 'C', 'D', 'E'] as $letter)
                                                        <button type="button"
                                                                @click.stop="saveAnswer({{ $q->id }}, '{{ $letter }}')"
                                                                class="w-7 h-7 rounded-lg font-bold text-xs transition-all border"
                                                                :class="answers['{{ $q->id }}'] === '{{ $letter }}' ? 'bg-blue-600 text-white border-blue-600 shadow ring-2 ring-blue-300' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300'">
                                                            {{ $letter }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- PART 3: ACADEMIC PRESENTATION (MULTIPLE CHOICE 21–30) --}}
                <div x-show="activeListeningPart === 3" x-cloak class="space-y-6">
                    @php $p3Group = $groups->firstWhere('order', 3) ?? $groups->skip(2)->first(); @endphp
                    @if($p3Group)
                        {{-- Instruction Box --}}
                        <div class="bg-blue-50/80 border-l-4 border-blue-600 p-4 rounded-r-2xl shadow-sm text-xs text-blue-950 font-medium">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-extrabold uppercase text-[11px] text-blue-800 tracking-wider">IELTS Listening • Part 3: Academic Presentation on Marine Biology</span>
                                <span class="px-2 py-0.5 rounded bg-blue-200 text-blue-800 font-bold text-[10px]">10 câu hỏi (21–30)</span>
                            </div>
                            <p class="font-bold text-sm text-blue-900 mt-1">Questions 21–30: Choose the correct letter, A, B or C.</p>
                        </div>

                        <div class="bg-white rounded-3xl border-2 border-slate-300 p-6 md:p-8 shadow-sm space-y-6"
                             style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                @foreach($p3Group->questions as $q)
                                    <div class="p-4 rounded-2xl border border-slate-200 space-y-3"
                                         :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                         id="question-block-{{ $q->question_number }}"
                                         @click="setCurrentQuestion({{ $q->question_number }})">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-start gap-2.5">
                                                <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 mt-0.5"
                                                      :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                    {{ $q->question_number }}
                                                </span>
                                                <p class="text-sm font-semibold leading-relaxed" style="color: var(--text-main);">{{ $q->prompt }}</p>
                                            </div>
                                            <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1 rounded text-slate-400 hover:bg-slate-100" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''">
                                                <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                            </button>
                                        </div>
                                        <div class="ml-9 space-y-2">
                                            @foreach($q->options ?? [] as $opt)
                                                @php
                                                    $optKey = $opt['key'] ?? '';
                                                    $optText = $opt['text'] ?? '';
                                                @endphp
                                                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100/70 transition-colors"
                                                       :class="answers['{{ $q->id }}'] === '{{ $optKey }}' ? 'border-blue-600 bg-blue-50/40 font-semibold' : ''">
                                                    <input type="radio" name="listening_q_{{ $q->id }}" value="{{ $optKey }}" x-model="answers['{{ $q->id }}']" @change="saveAnswer({{ $q->id }}, '{{ $optKey }}')" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300">
                                                    <span class="text-xs"><strong>{{ $optKey }}.</strong> {{ $optText }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- PART 4: LECTURE NOTES COMPLETION (31–40) --}}
                <div x-show="activeListeningPart === 4" x-cloak class="space-y-6">
                    @php $p4Group = $groups->firstWhere('order', 4) ?? $groups->last(); @endphp
                    @if($p4Group)
                        {{-- Instruction Box --}}
                        <div class="bg-blue-50/80 border-l-4 border-blue-600 p-4 rounded-r-2xl shadow-sm text-xs text-blue-950 font-medium">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-extrabold uppercase text-[11px] text-blue-800 tracking-wider">IELTS Listening • Part 4: University Lecture Notes</span>
                                <span class="px-2 py-0.5 rounded bg-blue-200 text-blue-800 font-bold text-[10px]">10 câu hỏi (31–40)</span>
                            </div>
                            <p class="font-bold text-sm text-blue-900 mt-1">Questions 31–40: Complete the notes below. Write ONE WORD ONLY for each answer.</p>
                        </div>

                        {{-- Academic Lecture Notes Card --}}
                        <div class="bg-white rounded-3xl border-2 border-slate-300 p-6 md:p-10 shadow-sm"
                             style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                            <div class="border-b-2 border-slate-200 pb-5 mb-8">
                                <span class="text-[11px] font-black uppercase tracking-widest text-blue-600 mb-1 block">Biology Seminar Notes</span>
                                <h2 class="text-xl md:text-2xl font-black text-slate-900" style="color: var(--text-main);">
                                    CEPHALOPOD COGNITION & NEUROLOGICAL ADAPTATIONS
                                </h2>
                            </div>

                            <div class="space-y-8">
                                {{-- Heading 1: Decentralized Nervous System & Anatomy --}}
                                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4" style="background-color: var(--bg-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <span>🧠</span> Nervous System & Anatomy
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p4Group->questions->whereIn('question_number', [31, 34, 35]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-100/50 transition-colors"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}">
                                                <div class="flex items-center gap-3 flex-grow">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <div class="text-sm font-medium leading-relaxed flex-grow" style="color: var(--text-main);">
                                                        @php
                                                            $inputHtml = '<span class="inline-flex items-center mx-1.5 align-middle"><input type="text" tabindex="' . $q->question_number . '" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1.5 text-sm font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none w-44 sm:w-56 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                            $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                        @endphp
                                                        {!! $renderedPrompt !!}
                                                    </div>
                                                </div>
                                                <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1.5 rounded hover:bg-slate-200/60 text-slate-400" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''" title="Đánh dấu câu hỏi cần xem lại">
                                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Heading 2: Camouflage & Problem Solving --}}
                                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4" style="background-color: var(--bg-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <span>👁️</span> Camouflage & Laboratory Problem Solving
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p4Group->questions->whereIn('question_number', [32, 33, 36]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-100/50 transition-colors"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}">
                                                <div class="flex items-center gap-3 flex-grow">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <div class="text-sm font-medium leading-relaxed flex-grow" style="color: var(--text-main);">
                                                        @php
                                                            $inputHtml = '<span class="inline-flex items-center mx-1.5 align-middle"><input type="text" tabindex="' . $q->question_number . '" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1.5 text-sm font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none w-44 sm:w-56 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                            $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                        @endphp
                                                        {!! $renderedPrompt !!}
                                                    </div>
                                                </div>
                                                <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1.5 rounded hover:bg-slate-200/60 text-slate-400" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''" title="Đánh dấu câu hỏi cần xem lại">
                                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Heading 3: Lifespan, Sleep & Evolution --}}
                                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4" style="background-color: var(--bg-main); border-color: var(--border-color);">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <span>🧬</span> Lifespan, Sleep Patterns & Evolution
                                    </h3>
                                    <div class="space-y-3">
                                        @foreach($p4Group->questions->whereIn('question_number', [37, 38, 39, 40]) as $q)
                                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-100/50 transition-colors"
                                                 :class="{'ring-2 ring-blue-500 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                                 id="question-block-{{ $q->question_number }}">
                                                <div class="flex items-center gap-3 flex-grow">
                                                    <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                          :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                        {{ $q->question_number }}
                                                    </span>
                                                    <div class="text-sm font-medium leading-relaxed flex-grow" style="color: var(--text-main);">
                                                        @php
                                                            $inputHtml = '<span class="inline-flex items-center mx-1.5 align-middle"><input type="text" tabindex="' . $q->question_number . '" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1.5 text-sm font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none w-44 sm:w-56 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                            $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                        @endphp
                                                        {!! $renderedPrompt !!}
                                                    </div>
                                                </div>
                                                <button type="button" @click.stop="toggleFlag({{ $q->id }})" class="p-1.5 rounded hover:bg-slate-200/60 text-slate-400" :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''" title="Đánh dấu câu hỏi cần xem lại">
                                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Floating Highlight / Note Popup --}}
            <div x-show="selectionMenu.show"
                 x-cloak
                 :style="`top: ${selectionMenu.top}px; left: ${selectionMenu.left}px;`"
                 @mousedown.stop
                 class="fixed z-50 bg-slate-900 text-white rounded-xl shadow-2xl p-1.5 flex items-center gap-1.5 text-xs border border-slate-700 animate-fadeIn">
                <button type="button" @click="applyHighlight()" class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 rounded-lg flex items-center gap-1.5 font-bold shadow-sm transition-all active:scale-95">
                    <span>🖍 Tô vàng</span>
                </button>
                <div class="h-4 w-[1px] bg-slate-700"></div>
                <button type="button" @click="openNoteModalForSelection()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg flex items-center gap-1.5 font-bold shadow-sm transition-all active:scale-95">
                    <span>💬 Ghi chú</span>
                </button>
            </div>

            {{-- Highlight Context Action Menu --}}
            <div x-show="highlightActionMenu.show"
                 x-cloak
                 :style="`top: ${highlightActionMenu.top}px; left: ${highlightActionMenu.left}px;`"
                 @mousedown.stop
                 class="fixed z-50 bg-slate-900 text-white rounded-xl shadow-2xl p-2 flex flex-col gap-1 text-xs border border-slate-700 animate-fadeIn min-w-[170px]">
                <div class="text-[10px] text-slate-400 font-bold uppercase px-2 py-0.5 tracking-wider border-b border-slate-800 pb-1 mb-1">
                    Tùy chọn Tô sáng
                </div>
                <button type="button" @click="clearCurrentHighlight()" class="w-full px-2.5 py-1.5 hover:bg-red-500/20 text-red-300 hover:text-red-200 rounded-lg flex items-center gap-2 text-left font-semibold transition-colors">
                    <span>🗑️ Xóa tô sáng này</span>
                </button>
                <button type="button" @click="openNoteModalForExistingHighlight()" class="w-full px-2.5 py-1.5 hover:bg-blue-500/20 text-blue-300 hover:text-blue-200 rounded-lg flex items-center gap-2 text-left font-semibold transition-colors">
                    <span>📝 Xem / Sửa ghi chú</span>
                </button>
                <div class="h-[1px] bg-slate-800 my-0.5"></div>
                <button type="button" @click="clearAllHighlights()" class="w-full px-2.5 py-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg flex items-center gap-2 text-left text-[11px] transition-colors">
                    <span>🧹 Xóa toàn bộ tô sáng</span>
                </button>
            </div>
        </div>

    {{-- CASE C: READING WORKSPACE (AUTHENTIC SPLIT SCREEN: PASSAGE ON LEFT, QUESTIONS ON RIGHT) --}}
    @else
        <div class="flex-grow flex flex-col md:flex-row overflow-hidden relative"
             style="background-color: var(--bg-main);"
             @mouseup="handleTextSelection($event)">

            {{-- LEFT COLUMN: PASSAGE OR LISTENING INSTRUCTIONS --}}
            <div class="w-full md:w-1/2 h-1/2 md:h-full overflow-y-auto custom-scroll p-6 md:p-8 border-r-2 border-slate-300 relative"
                 id="passage-container"
                 style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">

                @if($isReading)
                    @foreach($groups->whereNotNull('passage_content') as $passageGroup)
                        <div x-show="activePassageId === {{ $passageGroup->id }}" x-cloak class="prose max-w-none leading-relaxed">
                            <div class="text-xs uppercase font-extrabold tracking-wider text-blue-600 mb-2">Reading Passage {{ $loop->iteration }}</div>
                            <div class="passage-html-body" id="passage-content-{{ $passageGroup->id }}">
                                {!! $passageGroup->passage_content !!}
                            </div>
                        </div>
                    @endforeach
                @elseif($isListening)
                    @foreach($groups as $partGroup)
                        <div x-show="activePartId === {{ $partGroup->id }}" x-cloak class="prose max-w-none leading-relaxed space-y-4">
                            <div class="text-xs uppercase font-extrabold tracking-wider text-blue-600">IELTS Listening • Part {{ $loop->iteration }}</div>
                            <h2 class="text-lg font-black text-slate-900" style="color: var(--text-main);">{{ $partGroup->title }}</h2>
                            @if($partGroup->instruction)
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded-r-lg text-xs text-blue-900 font-medium">
                                    {{ $partGroup->instruction }}
                                </div>
                            @endif
                            <div class="text-xs text-slate-500 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-200">
                                💡 <strong>Hướng dẫn:</strong> Audio sẽ tự động phát nội dung cuộc trò chuyện / bài giảng của Part này. Bạn hãy theo dõi câu hỏi ở cột bên phải và nhập câu trả lời trực tiếp.
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- RIGHT COLUMN: QUESTIONS & INLINE ANSWER INPUTS --}}
            <div class="w-full md:w-1/2 h-1/2 md:h-full overflow-y-auto custom-scroll p-6 md:p-8 space-y-8"
                 id="questions-container"
                 style="background-color: var(--bg-main); color: var(--text-main);">

                @php
                    $passages = $groups->whereNotNull('passage_content')->values();
                @endphp
                @foreach($groups as $group)
                    @php
                        $firstQNum = $group->questions->first()?->question_number ?? 1;
                        $groupPassageId = ($firstQNum <= 13)
                            ? ($passages->get(0)?->id ?? 1)
                            : (($firstQNum <= 26)
                                ? ($passages->get(1)?->id ?? 2)
                                : ($passages->get(2)?->id ?? 3));
                    @endphp
                    <div x-show="activePassageId === {{ $groupPassageId }}"
                         x-cloak
                         class="border-2 border-slate-200 rounded-2xl p-5 md:p-6 shadow-sm mb-6"
                         style="background-color: var(--bg-card); border-color: var(--border-color);">
                        {{-- Instruction Box --}}
                        @if($group->instruction)
                            <div class="bg-amber-50 border-l-4 border-amber-500 p-3 rounded-r-lg mb-6 text-xs text-amber-900 font-medium">
                                <span class="font-bold block uppercase text-[11px] text-amber-800">Hướng dẫn làm bài:</span>
                                {{ $group->instruction }}
                            </div>
                        @endif

                        {{-- Questions List --}}
                        <div class="space-y-6">
                            @foreach($group->questions as $q)
                                <div class="question-item pt-4 border-t border-slate-200 first:border-t-0 first:pt-0"
                                     id="question-block-{{ $q->question_number }}"
                                     style="border-color: var(--border-color);"
                                     :class="{'ring-2 ring-blue-500 rounded-xl p-3 bg-blue-50/20': currentQuestionNumber === {{ $q->question_number }}}"
                                     @click="setCurrentQuestion({{ $q->question_number }})">

                                    @php
                                        $hasBlank = preg_match('/\[blank(_\d+)?\]|__{2,}/', $q->prompt);
                                        $hasOptions = $q->options && is_array($q->options) && count($q->options) > 0;
                                    @endphp

                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="flex items-start gap-2.5 flex-grow">
                                            <span class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 mt-0.5"
                                                  :class="{'bg-blue-600': currentQuestionNumber === {{ $q->question_number }}}">
                                                {{ $q->question_number }}
                                            </span>
                                            <div class="font-medium text-sm leading-loose flex-grow" style="color: var(--text-main);">
                                                @if(!$hasOptions && $hasBlank)
                                                    @php
                                                        $inputHtml = '<span class="inline-flex items-center mx-1 align-middle"><input type="text" x-model="answers[\'' . $q->id . '\']" @input.debounce.300ms="saveAnswer(' . $q->id . ', answers[\'' . $q->id . '\'])" @focus="setCurrentQuestion(' . $q->question_number . ')" placeholder="' . $q->question_number . '..." class="inline-block px-3 py-1 text-sm font-semibold rounded-lg border-2 border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none w-36 sm:w-44 shadow-inner transition-all text-center" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" /></span>';
                                                        $renderedPrompt = preg_replace('/\[blank(_\d+)?\]|__{2,}/', $inputHtml, e($q->prompt));
                                                    @endphp
                                                    {!! $renderedPrompt !!}

                                                    @if($q->word_limit)
                                                        <span class="inline-block text-[11px] text-slate-400 font-normal ml-2">(Tối đa {{ $q->word_limit }} từ)</span>
                                                    @endif
                                                @else
                                                    {{ $q->prompt }}
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Review Flag toggle on individual question --}}
                                        <button type="button"
                                                @click.stop="toggleFlag({{ $q->id }})"
                                                class="p-1 rounded hover:bg-slate-100 text-slate-400 flex-shrink-0 transition-colors mt-0.5"
                                                :class="isFlagged({{ $q->id }}) ? 'text-amber-500 font-bold' : ''"
                                                title="Đánh dấu câu hỏi cần xem lại">
                                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                                <path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Question Interaction Type Formats --}}
                                    @if($hasOptions)
                                        <div class="ml-9 mt-3">
                                            <div class="space-y-2">
                                                @foreach($q->options as $opt)
                                                    @php
                                                        $optKey = $opt['key'] ?? (is_string($opt) ? $opt : '');
                                                        $optText = $opt['text'] ?? (is_string($opt) ? $opt : '');
                                                    @endphp
                                                    <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100/80 transition-colors"
                                                           :class="answers['{{ $q->id }}'] === '{{ $optKey }}' ? 'border-blue-500 bg-blue-50/40 font-semibold' : ''"
                                                           style="border-color: answers['{{ $q->id }}'] === '{{ $optKey }}' ? '#3b82f6' : 'var(--border-color)';">
                                                        <input type="radio"
                                                               name="question_{{ $q->id }}"
                                                               value="{{ $optKey }}"
                                                               x-model="answers['{{ $q->id }}']"
                                                               @change="saveAnswer({{ $q->id }}, '{{ $optKey }}')"
                                                               class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300">
                                                        <span class="text-xs" style="color: var(--text-main);">
                                                            <strong>{{ $optKey }}.</strong> {{ $optText }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif(!$hasBlank)
                                        {{-- Fallback: Fill in blank without [blank] token in prompt --}}
                                        <div class="ml-9 mt-3">
                                            <div class="relative max-w-sm">
                                                <input type="text"
                                                       x-model="answers['{{ $q->id }}']"
                                                       @input.debounce.400ms="saveAnswer({{ $q->id }}, answers['{{ $q->id }}'])"
                                                       placeholder="Nhập câu trả lời của bạn..."
                                                       class="w-full px-3.5 py-2 text-xs font-semibold rounded-xl border-2 border-slate-300 focus:border-blue-500 focus:ring-0 outline-none"
                                                       style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                                                @if($q->word_limit)
                                                    <span class="text-[10px] text-slate-400 mt-1 block">Tối đa {{ $q->word_limit }} từ</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Floating Highlight / Note Popup --}}
            <div x-show="selectionMenu.show"
                 x-cloak
                 :style="`top: ${selectionMenu.top}px; left: ${selectionMenu.left}px;`"
                 @mousedown.stop
                 class="fixed z-50 bg-slate-900 text-white rounded-xl shadow-2xl p-1.5 flex items-center gap-1.5 text-xs border border-slate-700 animate-fadeIn">
                <button type="button" @click="applyHighlight()" class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 rounded-lg flex items-center gap-1.5 font-bold shadow-sm transition-all active:scale-95">
                    <span>🖍 Tô vàng</span>
                </button>
                <div class="h-4 w-[1px] bg-slate-700"></div>
                <button type="button" @click="openNoteModalForSelection()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg flex items-center gap-1.5 font-bold shadow-sm transition-all active:scale-95">
                    <span>💬 Ghi chú</span>
                </button>
            </div>

            {{-- Highlight Context Action Menu (Clear / Edit Note / Clear All) --}}
            <div x-show="highlightActionMenu.show"
                 x-cloak
                 :style="`top: ${highlightActionMenu.top}px; left: ${highlightActionMenu.left}px;`"
                 @mousedown.stop
                 class="fixed z-50 bg-slate-900 text-white rounded-xl shadow-2xl p-2 flex flex-col gap-1 text-xs border border-slate-700 animate-fadeIn min-w-[170px]">
                <div class="text-[10px] text-slate-400 font-bold uppercase px-2 py-0.5 tracking-wider border-b border-slate-800 pb-1 mb-1">
                    Tùy chọn Tô sáng
                </div>
                <button type="button" @click="clearCurrentHighlight()" class="w-full px-2.5 py-1.5 hover:bg-red-500/20 text-red-300 hover:text-red-200 rounded-lg flex items-center gap-2 text-left font-semibold transition-colors">
                    <span>🗑️ Xóa tô sáng này</span>
                </button>
                <button type="button" @click="openNoteModalForExistingHighlight()" class="w-full px-2.5 py-1.5 hover:bg-blue-500/20 text-blue-300 hover:text-blue-200 rounded-lg flex items-center gap-2 text-left font-semibold transition-colors">
                    <span>📝 Xem / Sửa ghi chú</span>
                </button>
                <div class="h-[1px] bg-slate-800 my-0.5"></div>
                <button type="button" @click="clearAllHighlights()" class="w-full px-2.5 py-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg flex items-center gap-2 text-left text-[11px] transition-colors">
                    <span>🧹 Xóa toàn bộ tô sáng</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- 4. FIXED FOOTER BAR (QUESTION PALETTE & CONTROLS) --}}
    {{-- ========================================================================= --}}
    <footer class="bg-white border-t-2 border-slate-300 px-4 py-2.5 flex flex-wrap items-center justify-between shadow-lg z-30 flex-shrink-0 gap-3"
            style="background-color: var(--bg-header); color: var(--text-main); border-color: var(--border-color);">

        @if($isWriting)
            {{-- Writing Controls --}}
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">
                    Task đang làm: <strong class="text-amber-600 font-bold">Task <span x-text="currentTaskNumber">1</span></strong>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="currentTaskNumber = 1" class="px-3 py-1.5 rounded-lg border text-xs font-bold"
                        :class="currentTaskNumber === 1 ? 'bg-amber-600 text-white border-amber-600' : 'bg-slate-100 text-slate-700 border-slate-300'">
                    Task 1 (Report)
                </button>
                <button type="button" @click="currentTaskNumber = 2" class="px-3 py-1.5 rounded-lg border text-xs font-bold"
                        :class="currentTaskNumber === 2 ? 'bg-amber-600 text-white border-amber-600' : 'bg-slate-100 text-slate-700 border-slate-300'">
                    Task 2 (Essay)
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="confirmSubmitModal = true" class="px-5 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md shadow-blue-600/25 active:scale-95 transition-all">
                    Nộp bài Writing (AI Chấm ngay)
                </button>
            </div>

        @else
            {{-- Reading & Listening Controls --}}
            <div class="flex items-center gap-3">
                <button type="button"
                        @click="toggleCurrentQuestionFlag()"
                        class="px-3 py-1.5 rounded-lg border flex items-center gap-2 text-xs font-bold transition-all shadow-sm"
                        :class="isCurrentQuestionFlagged ? 'bg-amber-100 border-amber-500 text-amber-800' : 'bg-slate-100 border-slate-300 text-slate-700'"
                        style="background-color: var(--bg-card); border-color: var(--border-color); color: var(--text-main);">
                    <svg class="w-4 h-4 fill-current" :class="isCurrentQuestionFlagged ? 'text-amber-500' : 'text-slate-400'" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 011-1h12a1 1 0 01.8 1.6L14.25 8l2.55 4.4A1 1 0 0116 14H5v3a1 1 0 11-2 0V3z"></path>
                    </svg>
                    <span>Review</span>
                </button>
                <div class="text-xs text-slate-500 font-medium hidden sm:block">
                    Đã trả lời: <strong class="text-slate-800 font-bold" x-text="answeredCount">0</strong> / {{ $submission->total_questions }} câu
                </div>
            </div>

            {{-- Question Palette 1..40 buttons --}}
            <div class="flex items-center gap-2 overflow-x-auto max-w-full sm:max-w-3xl py-2 px-1 custom-scroll">
                @for($i = 1; $i <= $submission->total_questions; $i++)
                    @php
                        $questionModel = $submission->section->questions->where('question_number', $i)->first();
                        $qId = $questionModel?->id ?? 0;
                    @endphp
                    <button type="button"
                            @click="setCurrentQuestion({{ $i }}); scrollToQuestion({{ $i }})"
                            class="relative shrink-0 w-10 h-10 rounded-lg text-sm font-bold flex items-center justify-center
                                border-2 shadow-sm select-none
                                transition-all duration-150 ease-out
                                hover:-translate-y-0.5 hover:shadow-md active:scale-95
                                focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                            :class="{
                                'bg-blue-600 text-white border-blue-600 ring-4 ring-blue-200 scale-110 z-10': currentQuestionNumber === {{ $i }},
                                'bg-slate-800 text-white border-slate-800 hover:bg-slate-700': isAnswered({{ $qId }}) && currentQuestionNumber !== {{ $i }},
                                'bg-white text-slate-700 border-slate-300 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700': !isAnswered({{ $qId }}) && currentQuestionNumber !== {{ $i }}
                            }">
                        <span>{{ $i }}</span>
                        {{-- Review dot badge --}}
                        <span x-show="isFlagged({{ $qId }})"
                            x-cloak
                            class="absolute -top-1.5 -right-1.5 w-3.5 h-3.5 bg-amber-500 rounded-full border-2 border-white shadow"></span>
                    </button>
                @endfor
            </div>

            {{-- Right Controls: Prev, Next, Submit --}}
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="prevQuestion()"
                        :disabled="currentQuestionNumber <= 1"
                        class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold text-slate-700 shadow-sm transition-all"
                        style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                    &larr; Prev
                </button>

                <button type="button"
                        @click="nextQuestion()"
                        :disabled="currentQuestionNumber >= {{ $submission->total_questions }}"
                        class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold text-slate-700 shadow-sm transition-all"
                        style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);">
                    Next &rarr;
                </button>

                <button type="button"
                        @click="confirmSubmitModal = true"
                        class="px-4 py-1.5 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md shadow-blue-600/25 active:scale-95 transition-all">
                    Nộp bài (Submit)
                </button>
            </div>
        @endif
    </footer>

    {{-- ========================================================================= --}}
    {{-- MODAL CẢNH BÁO 10 PHÚT VÀ 5 PHÚT CHUẨN IDP --}}
    {{-- ========================================================================= --}}
    <div x-show="timeWarningModal.show"
         x-cloak
         class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 text-slate-800 shadow-2xl border-2"
             :class="timeWarningModal.is5Min ? 'border-red-500 ring-4 ring-red-500/20' : 'border-amber-500 ring-4 ring-amber-500/20'">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-2xl flex-shrink-0"
                     :class="timeWarningModal.is5Min ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'">
                    ⏰
                </div>
                <div>
                    <h3 class="text-base font-black tracking-tight"
                        :class="timeWarningModal.is5Min ? 'text-red-600' : 'text-amber-600'"
                        x-text="timeWarningModal.title">
                    </h3>
                    <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">IELTS Official Time Notification</span>
                </div>
            </div>

            <p class="text-sm font-bold text-slate-800 mb-2 leading-relaxed" x-text="timeWarningModal.message"></p>
            <p class="text-xs text-slate-500 mb-6 leading-relaxed" x-text="timeWarningModal.subMessage"></p>

            <button type="button"
                    @click="timeWarningModal.show = false"
                    class="w-full py-2.5 font-black text-xs rounded-xl shadow-md transition-all text-white active:scale-98"
                    :class="timeWarningModal.is5Min ? 'bg-red-600 hover:bg-red-700 shadow-red-600/30' : 'bg-amber-600 hover:bg-amber-700 shadow-amber-600/30'">
                OK (ĐÃ HIỂU)
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: XÁC NHẬN NỘP BÀI (SUBMIT CONFIRMATION) --}}
    {{-- ========================================================================= --}}
    <div x-show="confirmSubmitModal"
         x-cloak
         class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-slate-800"
             @click.away="confirmSubmitModal = false">
            <h3 class="text-lg font-black text-slate-900 mb-2">Bạn có chắc chắn muốn nộp bài?</h3>
            <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                Sau khi nộp bài, hệ thống sẽ kết thúc lượt thi và tự động chấm điểm Band Score của bạn.
            </p>

            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 mb-6 text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Phần thi:</span>
                    <strong class="text-slate-900 uppercase font-black">{{ $skill }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Đã trả lời:</span>
                    <strong class="text-emerald-600 font-bold" x-text="answeredCount">0</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Chưa trả lời:</span>
                    <strong class="text-red-500 font-bold" x-text="{{ $submission->total_questions }} - answeredCount">0</strong>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" @click="confirmSubmitModal = false" class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Tiếp tục làm bài
                </button>
                <button type="button" @click="submitExam()" class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-600/25">
                    Xác nhận nộp bài ngay
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: CẢNH BÁO CHUYỂN TAB (ANTI-CHEAT FOCUS MODE) --}}
    {{-- ========================================================================= --}}
    <div x-show="tabSwitchWarning"
         x-cloak
         class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-amber-500 rounded-2xl max-w-md w-full p-6 text-slate-800 text-center shadow-2xl">
            <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-3 text-2xl">
                ⚠️
            </div>
            <h3 class="text-lg font-black text-amber-600 mb-2">CẢNH BÁO: RỜI KHỎI MÀN HÌNH THI</h3>
            <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                Bạn vừa rời khỏi cửa sổ làm bài thi. Hành vi chuyển tab hoặc mở ứng dụng khác được lưu lại trong biên bản giám sát thi.
            </p>
            <p class="text-[11px] text-slate-400 mb-6">Số lần phát hiện: <span class="text-amber-600 font-bold" x-text="tabSwitchCount">1</span></p>
            <button type="button" @click="tabSwitchWarning = false" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs">
                Tôi hiểu và cam kết tuân thủ quy chế
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: HƯỚNG DẪN THI (HELP) --}}
    {{-- ========================================================================= --}}
    <div x-show="helpModal"
         x-cloak
         class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl text-slate-800"
             @click.away="helpModal = false">
            <h3 class="text-lg font-black text-slate-900 mb-4 flex items-center gap-2">
                <span>ℹ️</span> Hướng dẫn Thao tác Làm Bài Thi IELTS
            </h3>
            <div class="space-y-3 text-xs text-slate-600 leading-relaxed mb-6">
                @if($isWriting)
                    <p><strong>1. Cấu trúc bài viết:</strong> Task 1 yêu cầu tối thiểu 150 từ (khoảng 20 phút), Task 2 yêu cầu tối thiểu 250 từ (khoảng 40 phút).</p>
                    <p><strong>2. Bộ đếm từ tự động:</strong> Hệ thống đếm số từ theo thời gian thực ở góc trên trình soạn thảo.</p>
                    <p><strong>3. Phím tắt:</strong> Bạn có thể sử dụng các phím tắt tiêu chuẩn như <code>Ctrl+C</code>, <code>Ctrl+V</code>, <code>Ctrl+Z</code>.</p>
                @else
                    <p><strong>1. Tô sáng & Ghi chú:</strong> Bôi đen văn bản để chọn <em>Highlight</em> (tô vàng) hoặc <em>Note</em> (ghi chú). Nhấp vào đoạn tô vàng để xóa hoặc xem ghi chú.</p>
                    <p><strong>2. Điều hướng câu hỏi:</strong> Dùng thanh số 1..40 ở cuối màn hình hoặc nút <em>Next</em> / <em>Prev</em>.</p>
                @endif
                <p><strong>3. Tự động lưu:</strong> Mọi câu trả lời được hệ thống tự động lưu trữ sau mỗi thao tác.</p>
            </div>
            <button type="button" @click="helpModal = false" class="w-full py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">
                Đóng hướng dẫn
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: NHẬP GHI CHÚ (NOTE ON TEXT) --}}
    {{-- ========================================================================= --}}
    <div x-show="noteModal.show"
         x-cloak
         class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl text-slate-800"
             @click.away="noteModal.show = false">
            <h4 class="text-sm font-bold text-slate-900 mb-2">Ghi chú cho đoạn văn bản</h4>
            <textarea x-model="noteModal.text"
                      rows="3"
                      placeholder="Nhập ghi chú của bạn tại đây..."
                      class="w-full p-2.5 text-xs border border-slate-300 rounded-xl mb-4 focus:ring-0 focus:border-blue-500 outline-none"></textarea>
            <div class="flex items-center justify-between">
                <button type="button"
                        x-show="noteModal.targetMark && noteModal.targetMark.hasAttribute('data-note')"
                        @click="deleteNoteFromMark()"
                        class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg text-xs font-semibold">
                    Xóa ghi chú
                </button>
                <div class="flex gap-2 ml-auto">
                    <button type="button" @click="noteModal.show = false" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs">Hủy</button>
                    <button type="button" @click="saveNoteModal()" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold">Lưu ghi chú</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: THÔNG BÁO AUDIO KẾT THÚC (2 PHÚT KIỂM TRA ĐÁP ÁN) --}}
    {{-- ========================================================================= --}}
    <div x-show="audioEndedModal"
         x-cloak
         class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 text-slate-800 shadow-2xl border-2 border-blue-500 ring-4 ring-blue-500/20 text-center animate-fadeIn">
            <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mx-auto mb-3 text-2xl font-bold">
                🎧
            </div>
            <h3 class="text-lg font-black text-slate-900 mb-2">AUDIO BÀI THI ĐÃ KẾT THÚC</h3>
            <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                Phần phát âm thanh 30 phút đã hoàn tất. Bạn hiện có <strong>2 phút cuối cùng</strong> để rà soát toàn bộ đáp án từ Part 1 đến Part 4.
            </p>
            <button type="button" @click="audioEndedModal = false" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-colors shadow-md">
                Bắt đầu kiểm tra đáp án (2 phút)
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. ALPINE.JS SIMULATOR APPLICATION LOGIC --}}
    {{-- ========================================================================= --}}
    <script>
        function ieltsSimulator(config) {
            return {
                submissionId: config.submissionId,
                remainingSeconds: config.initialSeconds,
                endTime: Date.now() + (config.initialSeconds * 1000),
                totalQuestions: config.totalQuestions,
                skill: config.skill,
                activePassageId: {{ $groups->whereNotNull('passage_content')->first()?->id ?? 1 }},
                activePartId: {{ $groups->first()?->id ?? 1 }},
                activeListeningPart: 1,
                currentTaskNumber: 1,
                currentQuestionNumber: 1,
                answers: {},
                flags: {},
                notes: {},
                showTimer: true,
                timerDisplayMode: 'time',
                contrastClass: 'contrast-standard',
                fontSizeClass: 'font-size-standard',
                isFullscreen: false,
                saving: false,
                confirmSubmitModal: false,
                isSubmitting: false,
                submitError: '',
                tabSwitchWarning: false,
                tabSwitchCount: 0,
                helpModal: false,
                warning10MinShown: false,
                warning5MinShown: false,
                timeWarningModal: { show: false, title: '', message: '', subMessage: '', is5Min: false },
                selectionMenu: { show: false, top: 0, left: 0, range: null },
                highlightActionMenu: { show: false, top: 0, left: 0, targetMark: null },
                noteModal: { show: false, text: '', range: null, targetMark: null },
                timerInterval: null,

                // Listening Audio Stream State
                audioElement: null,
                audioCurrentTime: '00:00',
                audioDuration: '30:00',
                audioProgress: 0,
                audioVolume: 80,
                audioMuted: false,
                autoplayBlocked: false,
                audioPlaying: false,
                isAnswerCheckPhase: false,
                audioEndedModal: false,

                init() {
                    // Populate initial answers and flags
                    if (config.userAnswers) {
                        for (const [qId, data] of Object.entries(config.userAnswers)) {
                            this.answers[qId] = data.answer || '';
                            this.flags[qId] = data.is_flagged || false;
                            this.notes[qId] = data.notes || '';
                        }
                    }

                    // Khởi tạo audio nếu là Listening
                    if (this.skill === 'listening') {
                        this.$nextTick(() => {
                            this.initAudio();
                        });
                    }

                    // Khởi động đồng hồ đếm ngược drift-free bằng Date.now()
                    this.timerInterval = setInterval(() => {
                        const now = Date.now();
                        const diff = Math.max(0, Math.round((this.endTime - now) / 1000));
                        this.remainingSeconds = diff;

                        // Cảnh báo 10 phút (600s)
                        if (this.remainingSeconds <= 600 && !this.warning10MinShown) {
                            this.warning10MinShown = true;
                            this.showTimer = true;
                            this.timeWarningModal = {
                                show: true,
                                title: '10 MINUTES REMAINING',
                                message: 'You have 10 minutes remaining in this test. You can no longer hide the time remaining.',
                                subMessage: 'Bạn còn 10 phút làm bài. Kể từ thời điểm này, đồng hồ đếm ngược sẽ hiển thị cố định và không thể ẩn.',
                                is5Min: false
                            };
                        }

                        // Cảnh báo 5 phút (300s)
                        if (this.remainingSeconds <= 300 && !this.warning5MinShown) {
                            this.warning5MinShown = true;
                            this.showTimer = true;
                            this.timeWarningModal = {
                                show: true,
                                title: '5 MINUTES REMAINING',
                                message: 'You have 5 minutes remaining in this test. Please check all your answers.',
                                subMessage: 'Bạn chỉ còn 5 phút cuối cùng. Vui lòng kiểm tra lại tất cả các câu hỏi và câu đánh dấu Review.',
                                is5Min: true
                            };
                        }

                        if (this.remainingSeconds <= 0) {
                            clearInterval(this.timerInterval);
                            this.autoSubmitOnTimeUp();
                        }
                    }, 500);

                    // Focus Mode & Tab switch tracking
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.tabSwitchCount++;
                            this.tabSwitchWarning = true;
                            this.notifyTabSwitched();
                        }
                    });

                    // Phím tắt điều hướng nhanh
                    window.addEventListener('keydown', (e) => {
                        if (e.altKey && e.key === 'ArrowRight') this.nextQuestion();
                        if (e.altKey && e.key === 'ArrowLeft') this.prevQuestion();
                    });
                },

                // Audio player methods
                initAudio() {
                    this.audioElement = this.$refs.audioPlayer;
                    if (this.audioElement) {
                        this.audioElement.volume = this.audioVolume / 100;
                        const playPromise = this.audioElement.play();
                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                this.audioPlaying = true;
                                this.autoplayBlocked = false;
                            }).catch(() => {
                                this.autoplayBlocked = true;
                                this.audioPlaying = false;
                            });
                        }
                    }
                },

                startAudioStream() {
                    if (this.audioElement) {
                        this.audioElement.play().then(() => {
                            this.audioPlaying = true;
                            this.autoplayBlocked = false;
                        }).catch(err => {
                            console.error('Audio play error:', err);
                        });
                    }
                },

                toggleAudioMute() {
                    if (!this.audioElement) return;
                    this.audioMuted = !this.audioMuted;
                    this.audioElement.muted = this.audioMuted;
                },

                setAudioVolume(val) {
                    this.audioVolume = Number(val);
                    if (this.audioElement) {
                        this.audioElement.volume = this.audioVolume / 100;
                        if (this.audioVolume > 0 && this.audioMuted) {
                            this.audioMuted = false;
                            this.audioElement.muted = false;
                        }
                    }
                },

                updateAudioTime() {
                    if (!this.audioElement) return;
                    const cur = Math.floor(this.audioElement.currentTime);
                    const dur = Math.floor(this.audioElement.duration || 1800);
                    const cm = Math.floor(cur / 60);
                    const cs = cur % 60;
                    const dm = Math.floor(dur / 60);
                    const ds = dur % 60;
                    this.audioCurrentTime = `${String(cm).padStart(2,'0')}:${String(cs).padStart(2,'0')}`;
                    this.audioDuration = `${String(dm).padStart(2,'0')}:${String(ds).padStart(2,'0')}`;
                    this.audioProgress = dur > 0 ? (cur / dur) * 100 : 0;
                },

                onAudioEnded() {
                    this.audioPlaying = false;
                    this.isAnswerCheckPhase = true;
                    this.audioEndedModal = true;
                    if (this.remainingSeconds > 120) {
                        this.remainingSeconds = 120;
                        this.endTime = Date.now() + 120000;
                    }
                },

                getPartAnsweredCount(partIdx) {
                    const start = (partIdx - 1) * 10 + 1;
                    const end = partIdx * 10;
                    let count = 0;
                    for (let i = start; i <= end; i++) {
                        const qId = this.getQuestionIdByNumber(i);
                        if (this.answers[qId] && String(this.answers[qId]).trim() !== '') {
                            count++;
                        }
                    }
                    return count;
                },

                get formattedTime() {
                    const m = Math.floor(this.remainingSeconds / 60);
                    const s = this.remainingSeconds % 60;
                    if (this.timerDisplayMode === 'minutes' && this.remainingSeconds > 600) {
                        return `${m + 1} minutes left`;
                    }
                    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')} left`;
                },

                toggleTimerFormat() {
                    if (this.remainingSeconds > 600) {
                        this.timerDisplayMode = this.timerDisplayMode === 'time' ? 'minutes' : 'time';
                    }
                },

                get isTenMinutesOrLess() {
                    return this.remainingSeconds <= 600;
                },

                get isFiveMinutesOrLess() {
                    return this.remainingSeconds <= 300;
                },

                getWordCount(text) {
                    if (!text || typeof text !== 'string') return 0;
                    const words = text.trim().split(/\s+/).filter(Boolean);
                    return words.length;
                },

                get answeredCount() {
                    return Object.values(this.answers).filter(val => val && String(val).trim() !== '').length;
                },

                get flaggedCount() {
                    return Object.values(this.flags).filter(Boolean).length;
                },

                get isCurrentQuestionFlagged() {
                    const qId = this.getQuestionIdByNumber(this.currentQuestionNumber);
                    return !!this.flags[qId];
                },

                isAnswered(questionId) {
                    return !!(this.answers[questionId] && String(this.answers[questionId]).trim() !== '');
                },

                isFlagged(questionId) {
                    return !!this.flags[questionId];
                },

                setCurrentQuestion(num) {
                    this.currentQuestionNumber = num;
                    this.syncActiveSectionByQuestion(num);
                },

                nextQuestion() {
                    if (this.currentQuestionNumber < this.totalQuestions) {
                        this.setCurrentQuestion(this.currentQuestionNumber + 1);
                        this.scrollToQuestion(this.currentQuestionNumber);
                    }
                },

                prevQuestion() {
                    if (this.currentQuestionNumber > 1) {
                        this.setCurrentQuestion(this.currentQuestionNumber - 1);
                        this.scrollToQuestion(this.currentQuestionNumber);
                    }
                },

                scrollToQuestion(num) {
                    this.$nextTick(() => {
                        const el = document.getElementById(`question-block-${num}`);
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                },

                syncActiveSectionByQuestion(num) {
                    if (this.skill === 'listening') {
                        if (num <= 10) this.activeListeningPart = 1;
                        else if (num <= 20) this.activeListeningPart = 2;
                        else if (num <= 30) this.activeListeningPart = 3;
                        else this.activeListeningPart = 4;
                    } else if (this.skill === 'reading') {
                        @php $passages = $groups->whereNotNull('passage_content')->values(); @endphp
                        if (num <= 13) this.activePassageId = {{ $passages->get(0)?->id ?? 1 }};
                        else if (num <= 26) this.activePassageId = {{ $passages->get(1)?->id ?? 2 }};
                        else this.activePassageId = {{ $passages->get(2)?->id ?? 3 }};
                    }
                },

                getQuestionIdByNumber(num) {
                    const map = {
                        @foreach($submission->section->questions as $q)
                            {{ $q->question_number }}: {{ $q->id }},
                        @endforeach
                    };
                    return map[num] || 0;
                },

                toggleFlag(questionId) {
                    this.flags[questionId] = !this.flags[questionId];
                    this.saveAnswerData(questionId, { is_flagged: this.flags[questionId] });
                },

                toggleCurrentQuestionFlag() {
                    const qId = this.getQuestionIdByNumber(this.currentQuestionNumber);
                    if (qId) this.toggleFlag(qId);
                },

                saveAnswer(questionId, value) {
                    this.answers[questionId] = value;
                    this.saveAnswerData(questionId, { answer: value });
                },

                saveAnswerData(questionId, payload) {
                    this.saving = true;
                    fetch(config.saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            question_id: questionId,
                            ...payload,
                        }),
                    })
                    .then(res => res.json())
                    .finally(() => {
                        setTimeout(() => this.saving = false, 300);
                    });
                },

                notifyTabSwitched() {
                    fetch(config.saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ tab_switched: true }),
                    });
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen();
                        this.isFullscreen = true;
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                            this.isFullscreen = false;
                        }
                    }
                },

                // =========================================================
                // HIGHLIGHT & NOTES ENGINE
                // =========================================================
                handleGlobalClick(event) {
                    const mark = event.target.closest('.ielts-highlight');
                    if (mark) {
                        event.preventDefault();
                        event.stopPropagation();
                        const rect = mark.getBoundingClientRect();
                        this.highlightActionMenu = {
                            show: true,
                            top: Math.max(10, rect.top - 46),
                            left: Math.max(10, rect.left),
                            targetMark: mark,
                        };
                        this.selectionMenu.show = false;
                        return;
                    }
                    this.highlightActionMenu.show = false;
                },

                handleTextSelection(event) {
                    if (this.skill === 'writing') return;
                    const selection = window.getSelection();
                    if (!selection || selection.isCollapsed || !selection.toString().trim()) {
                        this.selectionMenu.show = false;
                        return;
                    }

                    const range = selection.getRangeAt(0);
                    const rect = range.getBoundingClientRect();

                    if (rect.width === 0 || rect.height === 0) {
                        this.selectionMenu.show = false;
                        return;
                    }

                    this.selectionMenu = {
                        show: true,
                        top: Math.max(10, rect.top - 46),
                        left: Math.max(10, rect.left + (rect.width / 2) - 75),
                        range: range.cloneRange(),
                    };
                },

                wrapRangeWithHighlight(range, noteText = null) {
                    if (!range || range.collapsed) return [];

                    const startNode = range.startContainer;
                    const endNode = range.endContainer;

                    const wrapTextNode = (textNode, start, end, isFirst) => {
                        if (!textNode || textNode.nodeType !== Node.TEXT_NODE) return null;
                        const val = textNode.nodeValue;
                        if (start >= end || start >= val.length) return null;

                        let target = textNode;
                        if (end < val.length) target.splitText(end);
                        if (start > 0) target = target.splitText(start);

                        const mark = document.createElement('mark');
                        mark.className = 'ielts-highlight';
                        mark.title = 'Nhấp để xem tùy chọn xóa hoặc ghi chú';
                        if (noteText && isFirst) {
                            mark.setAttribute('data-note', noteText);
                            mark.title = 'Ghi chú: ' + noteText;
                        }

                        target.parentNode.insertBefore(mark, target);
                        mark.appendChild(target);
                        return mark;
                    };

                    const marks = [];

                    if (startNode === endNode && startNode.nodeType === Node.TEXT_NODE) {
                        const m = wrapTextNode(startNode, range.startOffset, range.endOffset, true);
                        if (m) marks.push(m);
                        return marks;
                    }

                    const commonAncestor = range.commonAncestorContainer;
                    const treeWalker = document.createTreeWalker(
                        commonAncestor,
                        NodeFilter.SHOW_TEXT,
                        {
                            acceptNode: (node) => {
                                return range.intersectsNode(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                            }
                        }
                    );

                    const textNodes = [];
                    while (treeWalker.nextNode()) {
                        textNodes.push(treeWalker.currentNode);
                    }

                    textNodes.forEach((node, idx) => {
                        const s = (node === startNode) ? range.startOffset : 0;
                        const e = (node === endNode) ? range.endOffset : node.nodeValue.length;
                        const m = wrapTextNode(node, s, e, idx === 0);
                        if (m) marks.push(m);
                    });

                    return marks;
                },

                applyHighlight() {
                    if (!this.selectionMenu.range) return;
                    this.wrapRangeWithHighlight(this.selectionMenu.range, null);
                    this.selectionMenu.show = false;
                    window.getSelection().removeAllRanges();
                },

                openNoteModalForSelection() {
                    if (!this.selectionMenu.range) return;
                    this.noteModal = {
                        show: true,
                        text: '',
                        range: this.selectionMenu.range.cloneRange(),
                        targetMark: null
                    };
                    this.selectionMenu.show = false;
                    window.getSelection().removeAllRanges();
                },

                openNoteModalForExistingHighlight() {
                    const mark = this.highlightActionMenu.targetMark;
                    if (!mark) return;
                    this.noteModal = {
                        show: true,
                        text: mark.getAttribute('data-note') || '',
                        range: null,
                        targetMark: mark
                    };
                    this.highlightActionMenu.show = false;
                },

                saveNoteModal() {
                    if (this.noteModal.targetMark) {
                        if (this.noteModal.text.trim()) {
                            this.noteModal.targetMark.setAttribute('data-note', this.noteModal.text.trim());
                            this.noteModal.targetMark.title = 'Ghi chú: ' + this.noteModal.text.trim();
                        } else {
                            this.noteModal.targetMark.removeAttribute('data-note');
                        }
                    } else if (this.noteModal.range) {
                        this.wrapRangeWithHighlight(this.noteModal.range, this.noteModal.text.trim());
                    }
                    this.noteModal.show = false;
                },

                deleteNoteFromMark() {
                    if (this.noteModal.targetMark) {
                        this.noteModal.targetMark.removeAttribute('data-note');
                        this.noteModal.targetMark.title = 'Nhấp để xem tùy chọn xóa hoặc ghi chú';
                    }
                    this.noteModal.show = false;
                },

                clearCurrentHighlight() {
                    const mark = this.highlightActionMenu.targetMark;
                    if (mark) {
                        const parent = mark.parentNode;
                        while (mark.firstChild) parent.insertBefore(mark.firstChild, mark);
                        parent.removeChild(mark);
                        parent.normalize();
                    }
                    this.highlightActionMenu.show = false;
                },

                clearAllHighlights() {
                    if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ các đoạn đã tô sáng trong bài thi?')) return;
                    document.querySelectorAll('.ielts-highlight').forEach(mark => {
                        const parent = mark.parentNode;
                        while (mark.firstChild) parent.insertBefore(mark.firstChild, mark);
                        parent.removeChild(mark);
                        parent.normalize();
                    });
                    this.highlightActionMenu.show = false;
                },

                submitExam() {
                    // Show full-screen loading overlay while AI is scoring
                    this.isSubmitting = true;
                    this.confirmSubmitModal = false;

                    fetch(config.submitUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ answers: this.answers }),
                    })
                    .then(res => {
                        if (!res.ok) {
                            return res.text().then(text => {
                                throw new Error('HTTP ' + res.status + ': ' + text.substring(0, 200));
                            });
                        }
                        return res.json();
                    })
                    .then(data => {
                        const url = data.redirect_url || config.resultUrl;
                        window.location.href = url;
                    })
                    .catch(err => {
                        console.error('Submit error:', err);
                        this.isSubmitting = false;
                        this.submitError = err.message || 'Lỗi khi nộp bài. Đang chuyển về trang kết quả...';
                        setTimeout(() => { window.location.href = config.resultUrl; }, 3500);
                    });
                },

                autoSubmitOnTimeUp() {
                    alert('Hết giờ làm bài! Hệ thống đang tự động nộp bài thi của bạn.');
                    this.submitExam();
                }
            };
        }
    </script>

    {{-- ========================================================================= --}}
    {{-- MODAL: ĐANG CHẤM ĐIỂM (AI SCORING LOADING OVERLAY) --}}
    {{-- ========================================================================= --}}
    <div x-show="isSubmitting"
         x-cloak
         class="fixed inset-0 z-[100] bg-slate-900/60 flex flex-col items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-8 text-center shadow-2xl">
            {{-- Animated spinner --}}
            <div class="w-16 h-16 mx-auto mb-6 relative">
                <div class="absolute inset-0 rounded-full border-4 border-slate-200"></div>
                <div class="absolute inset-0 rounded-full border-4 border-t-blue-600 border-r-transparent border-b-transparent border-l-transparent animate-spin"></div>
                <div class="absolute inset-2 rounded-full bg-white flex items-center justify-center text-2xl">
                    @if($isWriting) ✍️ @elseif($isListening) 🎧 @else 📖 @endif
                </div>
            </div>

            <h3 class="text-lg font-black text-slate-900 mb-2">
                @if($isWriting)
                    Gemini AI đang chấm bài Writing...
                @else
                    Đang tổng hợp & chấm điểm...
                @endif
            </h3>
            <p class="text-sm text-slate-500 mb-4 leading-relaxed">
                @if($isWriting)
                    Hệ thống đang gửi bài viết của bạn đến Gemini AI để đánh giá theo 4 tiêu chí chuẩn IELTS (Task Achievement, Coherence, Lexical Resource, Grammar). Vui lòng không đóng trình duyệt.
                @else
                    Hệ thống đang chấm điểm và tính Band Score. Quá trình này diễn ra trong vài giây.
                @endif
            </p>

            {{-- Animated dots --}}
            <div class="flex items-center justify-center gap-1.5">
                <span class="w-2.5 h-2.5 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0s;"></span>
                <span class="w-2.5 h-2.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0.15s;"></span>
                <span class="w-2.5 h-2.5 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.3s;"></span>
            </div>

            {{-- Error state (if submit fails) --}}
            <div x-show="submitError" x-cloak class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 text-left">
                <span class="font-bold text-red-600 block mb-1">⚠ Gặp sự cố:</span>
                <span x-text="submitError"></span>
                <span class="block mt-1 text-slate-400">Đang tự động chuyển về trang kết quả...</span>
            </div>
        </div>
    </div>
</body>
</html>
