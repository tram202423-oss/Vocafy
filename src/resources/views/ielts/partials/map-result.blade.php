@php
    $positionedQuestions = $group->questions->filter(
        fn ($question) => is_numeric($question->drop_x) && is_numeric($question->drop_y)
    );
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-sm space-y-5">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-wide text-blue-600">Plan / Map / Diagram Labeling</p>
        <h3 class="mt-1 text-lg font-bold text-slate-900">{{ $group->title }}</h3>
        @if($group->instruction)
            <p class="mt-2 text-sm text-slate-600">{{ $group->instruction }}</p>
        @endif
    </div>

    @if(filled($group->question_content))
        <div class="prose max-w-none rounded-xl bg-slate-50 p-4 text-sm">{!! $group->question_content !!}</div>
    @endif

    <figure>
        <div class="relative mx-auto w-fit max-w-full">
            <img src="{{ $group->image_url }}" alt="{{ $group->title }}" class="block max-h-[36rem] max-w-full rounded-xl border border-slate-200 object-contain">
            @foreach($positionedQuestions as $question)
                @php
                    $answer = trim((string) $question->correct_answer);
                    $left = max(0, min(100, (float) $question->drop_x));
                    $top = max(0, min(100, (float) $question->drop_y));
                @endphp
                <div class="absolute z-10 flex max-w-32 -translate-x-1/2 -translate-y-1/2 items-center gap-1 rounded-lg border-2 border-emerald-600 bg-white/95 px-2 py-1 text-xs font-bold text-emerald-900 shadow"
                     style="left: {{ $left }}%; top: {{ $top }}%;"
                     title="Câu {{ $question->question_number }}: {{ $answer ?: 'Chưa có đáp án chuẩn' }}"
                     aria-label="Vị trí đúng câu {{ $question->question_number }}{{ $answer !== '' ? ': '.$answer : '' }}">
                    <span class="shrink-0 rounded bg-emerald-600 px-1.5 py-0.5 text-white">{{ $question->question_number }}</span>
                    @if($answer !== '')
                        <span class="truncate">{{ \Illuminate\Support\Str::limit($answer, 16) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        <figcaption class="mt-3 text-center text-xs text-slate-500">
            @if($positionedQuestions->isNotEmpty())
                Các nhãn xanh chỉ vị trí và đáp án đúng trên sơ đồ.
            @else
                Đề này chưa lưu vị trí nhãn trên sơ đồ.
            @endif
        </figcaption>
    </figure>

    <div class="grid gap-2 sm:grid-cols-2">
        @foreach($group->questions as $question)
            @php
                $answer = trim((string) $question->correct_answer);
                $bankLabel = $group->answerOptions->first(fn ($option) => (string) $option->option_key === $answer)?->label;
                $questionOption = collect($question->options ?? [])->first(fn ($option) => is_array($option) && (string) ($option['key'] ?? '') === $answer);
                $answerLabel = $bankLabel ?: (is_array($questionOption) ? ($questionOption['text'] ?? null) : null);
            @endphp
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <span class="font-bold text-slate-900">Câu {{ $question->question_number }}</span>
                <span class="text-slate-500"> · </span>
                @if(is_numeric($question->drop_x) && is_numeric($question->drop_y))
                    <span class="font-semibold text-emerald-700">{{ $answer !== '' ? $answer : 'Chưa có đáp án chuẩn' }}</span>
                @else
                    <span class="font-semibold text-slate-700">{{ $answer !== '' ? $answer : 'Chưa có đáp án chuẩn' }}</span>
                    <span class="text-xs text-amber-700"> · chưa có vị trí trên ảnh</span>
                @endif
                @if(filled($answerLabel) && $answerLabel !== $answer)
                    <span class="text-slate-600">— {{ $answerLabel }}</span>
                @endif
            </div>
        @endforeach
    </div>
</section>
