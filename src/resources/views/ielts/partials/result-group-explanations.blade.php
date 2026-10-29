@php
    $explainedQuestions = $group->questions->filter(fn ($question) => filled($question->explanation) || filled($question->quote_reference));
@endphp
@if($explainedQuestions->isNotEmpty())
    <details class="rounded-xl border border-blue-200 bg-blue-50/50 p-4">
        <summary class="cursor-pointer text-xs font-bold text-blue-800">Lời giải bổ sung ({{ $explainedQuestions->count() }} câu)</summary>
        <div class="mt-3 space-y-3">
            @foreach($explainedQuestions as $question)
                <div class="rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-700">
                    <strong class="text-slate-900">Câu {{ $question->question_number }}</strong>
                    @if($question->quote_reference)
                        <p class="mt-1 italic">{{ $question->quote_reference }}</p>
                    @endif
                    @if($question->explanation)
                        <p class="mt-1">{{ $question->explanation }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </details>
@endif
