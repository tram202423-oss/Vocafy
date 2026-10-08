@php
    $dragQuestionIds = $group->questions->pluck('id')->values()->all();
    $dragOptionUsage = $group->option_usage ?? data_get($group->settings, 'drag_option_usage', 'repeat');
    $bankTitle = trim((string) data_get($group->settings, 'bank_title'));
    $bankTitle = $bankTitle !== '' ? $bankTitle : 'Ngân hàng từ';
@endphp

<section class="not-prose" aria-label="{{ $bankTitle }}"
         data-drop-answer-bank="true"
         data-drop-group-id="{{ $group->id }}">
    <div class="flex items-center justify-between gap-3 mb-3">
        <h3 class="text-sm font-black text-slate-800">{{ $bankTitle }}</h3>
        <span class="text-[11px] text-slate-500">
            {{ $dragOptionUsage === 'once' ? 'Mỗi từ chỉ dùng một lần' : 'Có thể dùng lại từ' }}
        </span>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($dragBank as $dragOption)
            <button type="button" draggable="false"
                    @pointerdown.stop="startDragPointer(@js((string) $dragOption['key']), @js((string) $dragOption['text']), null, @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }}, $event)"
                    @click.stop="selectDragOption(@js((string) $dragOption['key']), @js((string) $dragOption['text']), @js($dragQuestionIds), @js($dragOptionUsage), {{ $group->id }})"
                    :class="[
                        draggedOption?.key === @js((string) $dragOption['key']) ? 'border-blue-500 bg-blue-50 text-blue-800 ring-2 ring-blue-100' : 'border-slate-300 bg-white text-slate-700 hover:border-blue-400 hover:bg-blue-50',
                        @js($dragOptionUsage) === 'once' && isDragOptionUsed(@js((string) $dragOption['key']), @js($dragQuestionIds)) ? 'opacity-50 cursor-not-allowed' : 'cursor-grab active:cursor-grabbing'
                    ]"
                    class="select-none px-3 py-2 rounded-lg border-2 text-sm font-semibold shadow-sm transition">
                {{ $dragOption['text'] }}
                <span x-show="@js($dragOptionUsage) === 'once' && isDragOptionUsed(@js((string) $dragOption['key']), @js($dragQuestionIds))" x-cloak class="ml-1 text-[10px] font-normal">Đã dùng</span>
            </button>
        @endforeach
    </div>

    <p x-show="draggedOption" x-cloak class="mt-3 text-[11px] text-blue-700">
        <span x-text="dragPointer?.moved ? 'Thả đáp án vào ô được đánh dấu.' : `Đã chọn: ${draggedOption?.text || ''} — kéo thả hoặc bấm vào ô để đặt đáp án`"></span>
    </p>
    <p x-show="dragMessage" x-cloak x-text="dragMessage" class="mt-3 text-xs font-semibold text-amber-700"></p>
</section>
