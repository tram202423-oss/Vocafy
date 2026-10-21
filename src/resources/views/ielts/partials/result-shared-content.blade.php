@php
    $parts = preg_split('/(\[blank_\d+\])/', \App\Services\IeltsQuestionContentService::forDisplay($noteContent), -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp
<div class="passage-html-body">
    @foreach($parts as $part)
        @if(preg_match('/^\[blank_(\d+)\]$/', $part, $match))
            @php
                $number = (int) $match[1];
                $question = $group->questions->firstWhere('question_number', $number);
                $answer = $answers->firstWhere('question.question_number', $number);
                $isCorrect = (bool) $answer?->is_correct;
            @endphp
            @if($question)
                <span class="not-prose inline-flex flex-wrap items-center gap-1 mx-1 my-0.5 align-middle text-xs"
                      :class="filter === 'all' || filter === '{{ $isCorrect ? 'correct' : 'incorrect' }}' ? '' : 'opacity-30'">
                    <span class="rounded-l-md bg-slate-700 px-1.5 py-1 font-bold text-white">{{ $number }}</span>
                    <span class="rounded-r-md border px-2 py-1 font-semibold {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-300 bg-red-50 text-red-800' }}">{{ filled($answer?->user_answer) ? $answer->user_answer : '(Bỏ trống)' }}</span>
                    @unless($isCorrect)
                        <span class="text-slate-500">→</span>
                        <span class="rounded-md border border-emerald-300 bg-emerald-50 px-2 py-1 font-semibold text-emerald-800">{{ $question->correct_answer }}</span>
                    @endunless
                    @if($isListening)
                        <button type="button" @click="scrollToTranscriptQuestion({{ $number }})"
                                class="font-semibold text-blue-700 hover:underline">🎧</button>
                    @endif
                </span>
            @else
                {{ $part }}
            @endif
        @else
            {!! $part !!}
        @endif
    @endforeach
</div>
