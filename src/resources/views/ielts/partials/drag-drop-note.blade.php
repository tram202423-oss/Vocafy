@php
    $dragQuestionIds = $group->questions->pluck('id')->values()->all();
    $dragOptionUsage = data_get($group->settings, 'drag_option_usage', 'repeat');
    $passageParts = preg_split('/(\[blank_\d+\])/', $group->passage_content ?? '', -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp

<div class="passage-html-body leading-loose" id="passage-content-{{ $group->id }}">
    @foreach($passageParts as $passagePart)
        @if(preg_match('/^\[blank_(\d+)\]$/', $passagePart, $blankMatch))
            @php $blankQuestion = $group->questions->firstWhere('question_number', (int) $blankMatch[1]); @endphp
            @if($blankQuestion)
                <span id="question-block-{{ $blankQuestion->question_number }}"
                      tabindex="0"
                      @click.stop="setCurrentQuestion({{ $blankQuestion->question_number }}); if (draggedOption) assignDragOption({{ $blankQuestion->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage))"
                      @keydown.enter.prevent="if (draggedOption) assignDragOption({{ $blankQuestion->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage))"
                      @keydown.space.prevent="if (draggedOption) assignDragOption({{ $blankQuestion->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage))"
                      @dragover.prevent.stop="$event.currentTarget.classList.add('border-blue-500', 'bg-blue-50')"
                      @dragleave.stop="$event.currentTarget.classList.remove('border-blue-500', 'bg-blue-50')"
                      @drop.prevent.stop="if (draggedOption) assignDragOption({{ $blankQuestion->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage)); $event.currentTarget.classList.remove('border-blue-500', 'bg-blue-50')"
                      class="not-prose inline-flex min-w-[4.5rem] min-h-9 align-middle items-center justify-center mx-1 px-2 py-1 rounded border-2 border-slate-400 bg-white text-blue-800 font-bold text-sm cursor-pointer transition-colors"
                      :class="currentQuestionNumber === {{ $blankQuestion->question_number }} ? 'ring-2 ring-blue-300' : ''"
                      aria-label="Ô trống câu {{ $blankQuestion->question_number }}">
                    <span x-show="!answers['{{ $blankQuestion->id }}']" class="text-slate-500">{{ $blankQuestion->question_number }}</span>
                    @foreach($dragBank as $dragOption)
                        <span x-show="answers['{{ $blankQuestion->id }}'] === @js((string) $dragOption['key'])" x-cloak>{{ $dragOption['text'] }}</span>
                    @endforeach
                    <button type="button" x-show="answers['{{ $blankQuestion->id }}']" x-cloak
                            @click.stop="saveAnswer({{ $blankQuestion->id }}, ''); dragMessage = ''"
                            class="ml-2 text-slate-400 hover:text-red-600" aria-label="Xóa đáp án">×</button>
                </span>
            @else
                {!! $passagePart !!}
            @endif
        @else
            {!! $passagePart !!}
        @endif
    @endforeach
</div>
