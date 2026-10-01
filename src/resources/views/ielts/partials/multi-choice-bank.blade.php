@php
    $questions = $group->questions->values();
    $questionIds = $questions->pluck('id')->all();
    $selectionLimit = $questions->count();
    $options = \App\Services\IeltsMultiSelectService::options($group);
@endphp

<section class="rounded-xl border border-slate-200 p-4 md:p-5 space-y-4"
         aria-label="Câu hỏi trắc nghiệm chọn nhiều đáp án">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-black" style="color: var(--text-main);">
            Questions {{ $questions->first()?->question_number }}–{{ $questions->last()?->question_number }} · Choose {{ $selectionLimit }}
        </h3>
        <span class="text-xs font-semibold text-slate-500"
              x-text="`${multiSelected(@js($questionIds)).length} / {{ $selectionLimit }} selected`"></span>
    </div>
    @if($group->question_content)
        <div class="prose max-w-none">{!! $group->question_content !!}</div>
    @endif
    <p class="text-sm font-medium leading-relaxed" style="color: var(--text-main);">{{ $questions->first()?->prompt }}</p>
    <p class="text-xs text-slate-500">Chọn {{ $selectionLimit }} đáp án. Mỗi đáp án đúng được 1 điểm, không phân biệt thứ tự.</p>

    <div class="space-y-2">
        @foreach($options as $option)
            @php $key = \App\Services\IeltsMultiSelectService::normalize($option['key'] ?? ''); @endphp
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 cursor-pointer transition-colors"
                   :class="multiSelected(@js($questionIds)).includes(@js($key)) ? 'border-blue-500 bg-blue-50/50' : ''">
                <input type="checkbox" value="{{ $key }}"
                       :checked="multiSelected(@js($questionIds)).includes(@js($key))"
                       :disabled="isSubmitting || multiSaving[{{ $group->id }}] || (multiSelected(@js($questionIds)).length >= {{ $selectionLimit }} && !multiSelected(@js($questionIds)).includes(@js($key)))"
                       @change="setCurrentQuestion({{ $questions->first()?->question_number ?? 1 }}); updateMultiSelect({{ $group->id }}, @js($questionIds), @js($key), $event.target.checked)"
                       class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">
                <span class="text-sm" style="color: var(--text-main);"><strong class="mr-1">{{ $key }}.</strong>{{ $option['text'] ?? '' }}</span>
            </label>
        @endforeach
    </div>
    <p x-show="multiSelectMessages[{{ $group->id }}]" x-cloak x-text="multiSelectMessages[{{ $group->id }}]"
       class="text-xs font-semibold text-amber-700" role="status"></p>
    <div class="flex flex-wrap gap-2 text-xs">
        @foreach($questions as $question)
            <div id="question-block-{{ $question->question_number }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5">
                Question {{ $question->question_number }}: <strong x-text="answers[{{ $question->id }}] || '—'"></strong>
                <button type="button" @click="toggleFlag({{ $question->id }})"
                        :class="isFlagged({{ $question->id }}) ? 'text-amber-500' : 'text-slate-400'"
                        aria-label="Đánh dấu câu {{ $question->question_number }} để xem lại">⚑</button>
            </div>
        @endforeach
    </div>
</section>
