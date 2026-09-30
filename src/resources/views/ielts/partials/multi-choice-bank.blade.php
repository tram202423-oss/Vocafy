@php
    $questions = $group->questions->values();
    $questionIds = $questions->pluck('id')->all();
    $questionNumbers = $questions->pluck('question_number')->all();
    $firstQuestion = $questions->first();
    $options = $firstQuestion?->options ?? [];
    $selectionLimit = (int) data_get($group->settings, 'selection_limit', $questions->count());
    $selectionLimit = max(1, min($selectionLimit, $questions->count()));
    $firstNumber = $questions->first()?->question_number;
    $lastNumber = $questions->last()?->question_number;
@endphp

<section class="rounded-xl border border-slate-200 p-4 md:p-5 space-y-4"
         aria-label="Câu hỏi trắc nghiệm chọn nhiều đáp án">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-black text-slate-800">
            Questions {{ $firstNumber }}–{{ $lastNumber }} · Choose {{ strtoupper((string) $selectionLimit) }}
        </h3>
        <span class="text-xs font-semibold text-slate-500"
              x-text="`${(multiSelections[{{ $group->id }}] || []).length} / {{ $selectionLimit }} selected`"></span>
    </div>

    @if($firstQuestion)
        <p class="text-sm font-medium leading-relaxed" style="color: var(--text-main);">{{ $firstQuestion->prompt }}</p>
    @endif

    <p class="text-xs text-slate-500">
        Chọn tối đa {{ $selectionLimit }} đáp án. Các lựa chọn được gán vào câu {{ implode(', ', $questionNumbers) }} theo thứ tự bạn chọn.
    </p>

    <div class="grid sm:grid-cols-2 gap-2">
        @foreach($options as $option)
            @php
                $optionKey = (string) ($option['key'] ?? (is_string($option) ? $option : ''));
                $optionText = (string) ($option['text'] ?? (is_string($option) ? $option : ''));
            @endphp
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 cursor-pointer hover:bg-slate-50 transition-colors"
                   :class="(multiSelections[{{ $group->id }}] || []).includes(@js($optionKey)) ? 'border-blue-500 bg-blue-50/50' : ''">
                <input type="checkbox"
                       value="{{ $optionKey }}"
                       :checked="(multiSelections[{{ $group->id }}] || []).includes(@js($optionKey))"
                       @change="updateMultiSelect({{ $group->id }}, @js($questionIds), @js($optionKey), $event.target.checked, {{ $selectionLimit }})"
                       class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm" style="color: var(--text-main);">
                    <strong class="mr-1">{{ $optionKey }}.</strong>{{ $optionText }}
                </span>
            </label>
        @endforeach
    </div>

    <p x-show="multiSelectMessages[{{ $group->id }}]" x-cloak
       x-text="multiSelectMessages[{{ $group->id }}]"
       class="text-xs font-semibold text-amber-700" role="status"></p>

    <div class="flex flex-wrap gap-2 text-xs">
        @foreach($questionNumbers as $index => $questionNumber)
            <span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-slate-700">
                Question {{ $questionNumber }}:
                <strong x-text="(multiSelections[{{ $group->id }}] || [])[{{ $index }}] || '—'"></strong>
            </span>
        @endforeach
    </div>
</section>
