@php
    $dragQuestionIds = $group->questions->pluck('id')->values()->all();
    $dragOptionUsage = $group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat');
    $dragTextByKey = $dragBank->pluck('text', 'key')->all();
@endphp

<div class="space-y-3" aria-label="Các câu cần ghép đáp án">
    @foreach($group->questions as $question)
        <article id="question-block-{{ $question->question_number }}"
                 class="rounded-xl border border-slate-200 bg-white p-4 space-y-3"
                 style="background-color: var(--bg-card); border-color: var(--border-color);">
            <div class="flex items-start gap-3">
                <span class="inline-flex w-7 h-7 shrink-0 items-center justify-center rounded-full bg-slate-800 text-white text-xs font-bold">{{ $question->question_number }}</span>
                <p class="text-sm font-medium" style="color: var(--text-main);">{{ $question->prompt }}</p>
            </div>
            <div tabindex="0"
                 data-drop-question-id="{{ $question->id }}"
                 data-drop-group-id="{{ $group->id }}"
                 data-question-ids="{{ json_encode($dragQuestionIds) }}"
                 data-option-usage="{{ $dragOptionUsage }}"
                 draggable="false"
                 @pointerdown.stop="if (answers['{{ $question->id }}']) { const key = String(answers['{{ $question->id }}']); const textByKey = @js($dragTextByKey); startDragPointer(key, textByKey[key] || key, {{ $question->id }}, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }}, $event); }"
                 @click.stop="setCurrentQuestion({{ $question->question_number }}); if (draggedOption) assignDragOption({{ $question->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }})"
                 @keydown.enter.prevent="if (draggedOption) assignDragOption({{ $question->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }})"
                 class="min-h-11 flex items-center justify-between gap-3 rounded-lg border-2 border-dashed border-slate-300 px-3 py-2 cursor-pointer"
                 :class="currentQuestionNumber === {{ $question->question_number }} ? 'ring-2 ring-blue-300' : ''"
                 aria-label="Ô trả lời câu {{ $question->question_number }}">
                <span x-show="!answers['{{ $question->id }}']" class="text-xs text-slate-500">Thả đáp án vào đây</span>
                <span x-show="answers['{{ $question->id }}']" x-cloak class="text-sm font-bold text-blue-800" x-text="@js($dragTextByKey)[answers['{{ $question->id }}']] || answers['{{ $question->id }}']"></span>
                <button type="button" data-drag-clear x-show="answers['{{ $question->id }}']" x-cloak
                        @pointerdown.stop @keydown.stop @click.stop="clearDragAnswer({{ $question->id }})"
                        class="text-slate-400 hover:text-red-600" aria-label="Xóa đáp án câu {{ $question->question_number }}">×</button>
            </div>
        </article>
    @endforeach
</div>
