@php
    $bankTextByKey = $group->answerOptions->pluck('label', 'option_key')->all();
    foreach (data_get($group->settings, 'drag_options', []) as $option) {
        if (is_array($option) && filled($option['key'] ?? null) && filled($option['text'] ?? null)) {
            $bankTextByKey[(string) $option['key']] ??= (string) $option['text'];
        }
    }
@endphp

<div class="space-y-2" aria-label="Đề bài và đáp án từng câu">
    <p class="text-xs text-slate-500">Đáp án của bạn → đáp án đúng nếu trả lời sai.</p>
    @foreach($group->questions as $question)
        @php
            $answer = $groupAnswers->firstWhere('question.question_number', $question->question_number);
            $isCorrect = (bool) $answer?->is_correct;
            $selectedKey = trim((string) ($answer?->user_answer ?? ''));
            $correctKey = trim((string) $question->correct_answer);
            $questionTexts = collect($question->options ?? [])
                ->filter(fn ($option) => is_array($option) && filled($option['key'] ?? null))
                ->mapWithKeys(fn ($option) => [(string) $option['key'] => (string) ($option['text'] ?? '')])->all();
            $selectedText = $questionTexts[$selectedKey] ?? $bankTextByKey[$selectedKey] ?? null;
            $correctText = $questionTexts[$correctKey] ?? $bankTextByKey[$correctKey] ?? null;
        @endphp
        <article class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-start sm:justify-between"
                 x-show="filter === 'all' || (filter === 'correct' && {{ $isCorrect ? 'true' : 'false' }}) || (filter === 'incorrect' && {{ !$isCorrect ? 'true' : 'false' }})"
                 x-cloak>
            <div class="flex min-w-0 items-start gap-2 text-sm text-slate-800">
                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-white">{{ $question->question_number }}</span>
                <span class="break-words">{{ $question->prompt }}</span>
                @if($isListening)
                    <button type="button" @click="scrollToTranscriptQuestion({{ $question->question_number }})"
                            class="shrink-0 text-xs font-semibold text-blue-700 hover:underline">🎧 Transcript</button>
                @endif
            </div>
            <div class="flex min-w-0 flex-wrap items-center gap-1 text-xs sm:max-w-[48%] sm:justify-end">
                <span class="rounded-md border px-2 py-1 font-semibold break-words {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-300 bg-red-50 text-red-800' }}">
                    {{ $selectedKey !== '' ? $selectedKey : '(Bỏ trống)' }}@if(filled($selectedText)) · {{ $selectedText }}@endif
                </span>
                @unless($isCorrect)
                    <span class="text-slate-500">→</span>
                    <span class="rounded-md border border-emerald-300 bg-emerald-50 px-2 py-1 font-semibold text-emerald-800 break-words">
                        {{ $correctKey }}@if(filled($correctText)) · {{ $correctText }}@endif
                    </span>
                @endunless
            </div>
        </article>
    @endforeach
</div>
