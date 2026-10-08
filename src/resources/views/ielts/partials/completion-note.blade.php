@php
    $parts = preg_split('/(\[blank_\d+\])/', \App\Services\IeltsQuestionContentService::forDisplay($noteContent), -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp
<div class="passage-html-body leading-loose overflow-x-auto" id="question-content-{{ $group->id }}">
    @foreach($parts as $part)
        @if(preg_match('/^\[blank_(\d+)\]$/', $part, $match))
            @php $question = $group->questions->firstWhere('question_number', (int) $match[1]); @endphp
            @if($question)
                <span id="question-block-{{ $question->question_number }}" class="not-prose inline-flex items-center gap-1 mx-1 my-1 align-middle">
                    <label for="completion-{{ $question->id }}" class="sr-only">Câu {{ $question->question_number }}</label>
                    <input id="completion-{{ $question->id }}" type="text"
                           aria-label="Câu {{ $question->question_number }}" placeholder="{{ $question->question_number }}"
                           x-model="answers['{{ $question->id }}']"
                           @focus="setCurrentQuestion({{ $question->question_number }})"
                           @input.debounce.300ms="saveAnswer({{ $question->id }}, answers['{{ $question->id }}'])"
                           autocomplete="off" spellcheck="false" autocorrect="off" autocapitalize="off"
                           class="ielts-numbered-answer rounded-lg border border-slate-400 px-2 py-1 text-center text-sm focus:ring-2 focus:ring-blue-400"
                           style="width:10rem;max-width:100%;background-color:var(--bg-card);color:var(--text-main)">
                    @if($question->word_limit)
                        <small class="ml-1 text-[10px] text-slate-500">{{ \App\Services\IeltsWordLimitService::label($question) }}</small>
                    @endif
                    <button type="button" @click="toggleFlag({{ $question->id }})"
                            :class="isFlagged({{ $question->id }}) ? 'text-amber-500' : 'text-slate-400'"
                            aria-label="Đánh dấu câu {{ $question->question_number }} để xem lại">⚑</button>
                </span>
            @else
                {{ $part }}
            @endif
        @else
            {!! $part !!}
        @endif
    @endforeach
</div>
