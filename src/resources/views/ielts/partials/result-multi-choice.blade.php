@php
    $options = \App\Services\IeltsMultiSelectService::options($group);
    $selectedKeys = $groupAnswers
        ->map(fn ($answer) => \App\Services\IeltsMultiSelectService::normalize($answer->user_answer))
        ->filter()->unique()->values()->all();
    $correctKeys = \App\Services\IeltsMultiSelectService::correctKeys($group);
    $firstQuestion = $group->questions->first();
@endphp

<div class="space-y-3" aria-label="Các lựa chọn và kết quả trắc nghiệm chọn nhiều">
    @if(filled($firstQuestion?->prompt))
        <p class="text-sm font-semibold text-slate-900">{{ $firstQuestion->prompt }}</p>
    @endif
    <p class="text-xs text-slate-500">Mỗi đáp án đúng được một điểm; thứ tự lựa chọn không ảnh hưởng kết quả.</p>
    <div class="space-y-2">
        @foreach($options as $option)
            @php
                $key = \App\Services\IeltsMultiSelectService::normalize($option['key'] ?? '');
                $isCorrectOption = $key !== '' && in_array($key, $correctKeys, true);
                $isSelected = $key !== '' && in_array($key, $selectedKeys, true);
            @endphp
            <div class="flex flex-wrap items-center gap-2 rounded-lg border px-3 py-2 text-sm {{ $isCorrectOption ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : ($isSelected ? 'border-red-300 bg-red-50 text-red-900' : 'border-slate-200 bg-white text-slate-700') }}">
                <span><strong>{{ $option['key'] ?? '' }}.</strong> {{ $option['text'] ?? '' }}</span>
                @if($isCorrectOption)
                    <span class="rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Đáp án đúng{{ $isSelected ? ' · Bạn chọn' : '' }}</span>
                @elseif($isSelected)
                    <span class="rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Bạn chọn sai</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
