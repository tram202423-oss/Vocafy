@php
    $isMultiSelect = \App\Services\IeltsMultiSelectService::enabled($group);
    $isDragDrop = $group->response_mode === 'drag_drop' || $group->question_type?->value === 'drag_drop';
    $bank = $isMultiSelect
        ? collect(\App\Services\IeltsMultiSelectService::options($group))
        : $group->answerOptions->map(fn ($option) => ['key' => $option->option_key, 'text' => $option->label]);
    if ($bank->isEmpty() && $isDragDrop) {
        $bank = collect(data_get($group->settings, 'drag_options', []));
    }
    if ($bank->isEmpty() && $isDragDrop) {
        $bank = collect($group->questions->first()?->options ?? []);
    }
    $bank = $bank->filter(fn ($option) => is_array($option) && filled($option['key'] ?? null) && filled($option['text'] ?? null))
        ->unique(fn ($option) => (string) $option['key'])->values();
    $bankTitle = trim((string) data_get($group->settings, 'bank_title'));
    $bankTitle = $bankTitle !== '' ? $bankTitle : ($isMultiSelect ? 'Các lựa chọn' : 'Ngân hàng từ');
@endphp
@if($bank->isNotEmpty())
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h4 class="mb-2 text-xs font-bold text-slate-700">{{ $bankTitle }}</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($bank as $option)
                <span class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800">
                    <strong>{{ $option['key'] }}.</strong> {{ $option['text'] }}
                </span>
            @endforeach
        </div>
    </div>
@endif
