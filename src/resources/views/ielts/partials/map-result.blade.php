@php
    $positionedQuestions = $group->questions->filter(
        fn ($question) => is_numeric($question->drop_x) && is_numeric($question->drop_y)
    );
    $answersByNumber = $groupAnswers->keyBy(fn ($answer) => $answer->question?->question_number);
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
        <div class="prose max-w-none rounded-xl bg-slate-50 p-4 text-sm">{!! \App\Services\IeltsQuestionContentService::forDisplay($group->question_content) !!}</div>
    @endif

    @include('ielts.partials.result-answer-bank', ['group' => $group])

    <figure>
        <div class="relative mx-auto w-full max-w-4xl">
            <img src="{{ $group->image_url }}" alt="{{ $group->title }}" class="block h-auto w-full rounded-xl border border-slate-200">
            @foreach($positionedQuestions as $question)
                @php
                    $submitted = $answersByNumber->get($question->question_number);
                    $isCorrect = (bool) $submitted?->is_correct;
                    $selected = trim((string) ($submitted?->user_answer ?? ''));
                    $correct = trim((string) $question->correct_answer);
                    $left = max(0, min(100, (float) $question->drop_x));
                    $top = max(0, min(100, (float) $question->drop_y));
                @endphp
                <div class="absolute z-10 flex max-w-40 -translate-x-1/2 -translate-y-1/2 items-start gap-1 rounded-lg border border-slate-300 bg-white/95 p-1 text-[10px] font-semibold leading-tight text-slate-800 shadow"
                     style="left: {{ $left }}%; top: {{ $top }}%;"
                     title="Câu {{ $question->question_number }} · Đã chọn: {{ $selected !== '' ? $selected : '(Bỏ trống)' }} · Đúng: {{ $correct !== '' ? $correct : '(Chưa có đáp án chuẩn)' }}"
                     aria-label="Câu {{ $question->question_number }}: đáp án đã chọn {{ $selected !== '' ? $selected : 'bỏ trống' }}, đáp án đúng {{ $correct !== '' ? $correct : 'chưa có' }}, {{ $isCorrect ? 'đúng' : 'sai' }}">
                    <span class="shrink-0 rounded px-1.5 py-1 font-bold text-white {{ $isCorrect ? 'bg-emerald-600' : 'bg-red-600' }}">{{ $question->question_number }}</span>
                    <span class="min-w-0 break-words">
                        <span class="block">Bạn: {{ $selected !== '' ? $selected : '—' }}</span>
                        <span class="block text-emerald-800">Đúng: {{ $correct !== '' ? $correct : '—' }}</span>
                    </span>
                </div>
            @endforeach
        </div>
        <figcaption class="mt-3 text-center text-xs text-slate-500">
            @if($positionedQuestions->isNotEmpty())
                Số màu xanh là câu đúng, số màu đỏ là câu sai. Mỗi nhãn hiển thị đáp án đã chọn và đáp án đúng.
            @else
                Đề này chưa lưu vị trí nhãn trên sơ đồ.
            @endif
        </figcaption>
    </figure>

</section>
