@php
    $dragQuestionIds = $group->questions->pluck('id')->values()->all();
    $dragOptionUsage = $group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat');
    $passageParts = preg_split('/(\[blank_\d+\])/', \App\Services\IeltsQuestionContentService::forDisplay($noteContent ?? $group->question_content ?? $group->passage_content ?? ''), -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp

<div class="passage-html-body leading-loose" id="question-content-{{ $group->id }}">
    @foreach($passageParts as $passagePart)
        @if(preg_match('/^\[blank_(\d+)\]$/', $passagePart, $blankMatch))
            @php $blankQuestion = $group->questions->firstWhere('question_number', (int) $blankMatch[1]); @endphp
            @if($blankQuestion)
                <span id="question-block-{{ $blankQuestion->question_number }}"
                      tabindex="0"
                      data-drop-question-id="{{ $blankQuestion->id }}"
                      data-drop-group-id="{{ $group->id }}"
                      data-question-ids="{{ json_encode($dragQuestionIds) }}"
                      data-option-usage="{{ $dragOptionUsage }}"
                      draggable="false"
                      @pointerdown.stop="if (answers['{{ $blankQuestion->id }}']) { const key = String(answers['{{ $blankQuestion->id }}']); const textByKey = @js($dragBank->pluck('text', 'key')->all()); startDragPointer(key, textByKey[key] || key, {{ $blankQuestion->id }}, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }}, $event); }"
                      @click.stop="setCurrentQuestion({{ $blankQuestion->question_number }}); if (draggedOption) assignDragOption({{ $blankQuestion->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }})"
                      @keydown.enter.prevent="if (draggedOption) assignDragOption({{ $blankQuestion->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }})"
                      class="not-prose inline-flex min-w-[4.5rem] min-h-9 align-middle items-center justify-center mx-1 px-2 py-1 rounded border-2 border-slate-400 bg-white text-blue-800 font-bold text-sm cursor-pointer transition-colors"
                      :class="currentQuestionNumber === {{ $blankQuestion->question_number }} ? 'ring-2 ring-blue-300' : ''"
                      aria-label="Ô trống câu {{ $blankQuestion->question_number }}">
                    <span x-show="!answers['{{ $blankQuestion->id }}']" class="text-slate-500">{{ $blankQuestion->question_number }}</span>
                    @foreach($dragBank as $dragOption)
                        <span x-show="answers['{{ $blankQuestion->id }}'] === @js((string) $dragOption['key'])" x-cloak>{{ $dragOption['text'] }}</span>
                    @endforeach
                    <button type="button" data-drag-clear x-show="answers['{{ $blankQuestion->id }}']" x-cloak
                            @pointerdown.stop @keydown.stop @click.stop="clearDragAnswer({{ $blankQuestion->id }})"
                            class="ml-2 text-slate-400 hover:text-red-600" aria-label="Xóa đáp án câu {{ $blankQuestion->question_number }}">×</button>
                </span>
            @else
                {!! $passagePart !!}
            @endif
        @else
            {!! $passagePart !!}
        @endif
    @endforeach
</div>
