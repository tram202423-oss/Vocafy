@php
    $dragQuestionIds = $group->questions->pluck('id')->values()->all();
    $dragOptionUsage = $group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat');
    $dragTextByKey = $dragBank->pluck('text', 'key')->all();
    $mapQuestions = $group->questions->filter(fn ($question) => $question->drop_x !== null && $question->drop_y !== null);
    $unpositionedQuestions = $group->questions->filter(fn ($question) => $question->drop_x === null || $question->drop_y === null);
@endphp

@if($mapQuestions->isNotEmpty())
    <div class="relative mx-auto w-full max-w-4xl" aria-label="Sơ đồ có các ô thả đáp án">
        <img src="{{ $group->image_url }}" alt="{{ $group->title }}" class="block h-auto w-full rounded-xl">
        @foreach($mapQuestions as $question)
            <div id="question-block-{{ $question->question_number }}"
                 tabindex="0"
                 data-drop-question-id="{{ $question->id }}"
                 data-drop-group-id="{{ $group->id }}"
                 data-question-ids="{{ json_encode($dragQuestionIds) }}"
                 data-option-usage="{{ $dragOptionUsage }}"
                 :draggable="!!answers['{{ $question->id }}']"
                 @pointerdown.stop="if (answers['{{ $question->id }}']) { const key = String(answers['{{ $question->id }}']); const textByKey = @js($dragTextByKey); startDragPointer(key, textByKey[key] || key, {{ $question->id }}, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }}, $event); }"
                 @dragstart.stop="if (answers['{{ $question->id }}']) { const key = String(answers['{{ $question->id }}']); const textByKey = @js($dragTextByKey); draggedOption = { key, text: textByKey[key] || key, sourceQuestionId: {{ $question->id }} }; $event.dataTransfer?.setData('text/plain', key); }"
                 @dragend="if (draggedOption?.sourceQuestionId === {{ $question->id }}) draggedOption = null"
                 @click.stop="setCurrentQuestion({{ $question->question_number }}); if (draggedOption) assignDragOption({{ $question->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }})"
                 @dragover.prevent.stop="$event.currentTarget.classList.add('border-blue-600', 'bg-blue-100')"
                 @dragleave.stop="$event.currentTarget.classList.remove('border-blue-600', 'bg-blue-100')"
                 @drop.prevent.stop="if (draggedOption) assignDragOption({{ $question->id }}, draggedOption, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }}); $event.currentTarget.classList.remove('border-blue-600', 'bg-blue-100')"
                 style="left: {{ $question->drop_x }}%; top: {{ $question->drop_y }}%;"
                 class="absolute flex min-h-9 min-w-12 -translate-x-1/2 -translate-y-1/2 items-center justify-center gap-1 rounded border-2 border-dashed border-slate-700 bg-white/90 px-2 py-1 text-xs font-bold shadow"
                 aria-label="Ô trả lời câu {{ $question->question_number }}">
                <span x-show="!answers['{{ $question->id }}']" class="text-slate-700">{{ $question->question_number }}</span>
                <span x-show="answers['{{ $question->id }}']" x-cloak x-text="@js($dragTextByKey)[answers['{{ $question->id }}']] || answers['{{ $question->id }}']"></span>
                <button type="button" x-show="answers['{{ $question->id }}']" x-cloak @click.stop="saveAnswer({{ $question->id }}, '')" aria-label="Xóa đáp án câu {{ $question->question_number }}">×</button>
            </div>
        @endforeach
    </div>
@endif

@if($unpositionedQuestions->isNotEmpty())
    <div class="mt-5 space-y-3">
        @foreach($unpositionedQuestions as $question)
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="mb-3 text-sm font-medium"><strong>{{ $question->question_number }}.</strong> {{ $question->prompt }}</p>
            @include('ielts.partials.drag-drop-question-targets', ['group' => (object) ['id' => $group->id, 'questions' => collect([$question]), 'option_usage' => $dragOptionUsage, 'settings' => $group->settings], 'dragBank' => $dragBank])
            </div>
        @endforeach
    </div>
@endif
