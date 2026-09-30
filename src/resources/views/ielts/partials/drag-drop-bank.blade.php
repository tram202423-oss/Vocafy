@php
    $dragQuestionIds = $group->questions->pluck('id')->values()->all();
    $dragOptionUsage = data_get($group->settings, 'drag_option_usage', 'repeat');
@endphp

<section class="not-prose" aria-label="Ngân hàng từ kéo thả">
    <div class="flex items-center justify-between gap-3 mb-3">
        <h3 class="text-sm font-black text-slate-800">Ngân hàng từ</h3>
        <span class="text-[11px] text-slate-500">
            {{ $dragOptionUsage === 'once' ? 'Mỗi từ chỉ dùng một lần' : 'Có thể dùng lại từ' }}
        </span>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($dragBank as $dragOption)
            <button type="button" draggable="true"
                    @dragstart.stop="if (@js($dragOptionUsage) === 'once' && isDragOptionUsed(@js((string) $dragOption['key']), @js($dragQuestionIds))) { $event.preventDefault(); } else { draggedOption = { key: @js((string) $dragOption['key']), text: @js((string) $dragOption['text']) }; dragMessage = ''; }"
                    @dragend="draggedOption = null"
                    @click.stop="selectDragOption(@js((string) $dragOption['key']), @js((string) $dragOption['text']), @js($dragQuestionIds), @js($dragOptionUsage))"
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
        <span x-text="`Đã chọn: ${draggedOption?.text || ''} — bấm vào ô trống để đặt đáp án`"></span>
    </p>
    <p x-show="dragMessage" x-cloak x-text="dragMessage" class="mt-3 text-xs font-semibold text-amber-700"></p>
</section>
