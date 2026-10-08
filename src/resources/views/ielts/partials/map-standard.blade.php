@php
    $positionedQuestions = $group->questions->filter(fn ($question) => $question->drop_x !== null && $question->drop_y !== null);
    $unpositionedQuestions = $group->questions->filter(fn ($question) => $question->drop_x === null || $question->drop_y === null);
@endphp

<div class="space-y-5">
    <div class="relative mx-auto w-full max-w-4xl overflow-hidden rounded-xl border border-slate-200 bg-white">
        <img src="{{ $group->image_url }}" alt="{{ $group->title }}" class="block h-auto w-full">
        @foreach($positionedQuestions as $question)
            <div id="question-block-{{ $question->question_number }}"
                 class="absolute -translate-x-1/2 -translate-y-1/2 rounded-lg border-2 border-blue-600 bg-white/95 p-1.5 shadow-lg"
                 style="left: {{ $question->drop_x }}%; top: {{ $question->drop_y }}%; min-width: 7rem;">
                <label for="map-answer-{{ $question->id }}" class="sr-only">Câu {{ $question->question_number }}: {{ $question->prompt }}</label>
                @if(is_array($question->options) && count($question->options))
                    <select id="map-answer-{{ $question->id }}"
                            x-model="answers['{{ $question->id }}']"
                            @change="saveAnswer({{ $question->id }}, $event.target.value)"
                            class="max-w-48 rounded border-0 bg-transparent px-2 py-1 text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                        <option value="">Câu {{ $question->question_number }}</option>
                        @foreach($question->options as $option)
                            @php
                                $optionKey = is_array($option) ? ($option['key'] ?? '') : $option;
                                $optionText = is_array($option) ? ($option['text'] ?? '') : '';
                            @endphp
                            <option value="{{ $optionKey }}">{{ $optionKey }}{{ $optionText !== '' ? ' — '.$optionText : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="map-answer-{{ $question->id }}" type="text"
                           x-model="answers['{{ $question->id }}']"
                           @input.debounce.300ms="saveAnswer({{ $question->id }}, answers['{{ $question->id }}'])"
                           class="ielts-numbered-answer w-24 rounded border-0 px-2 py-1 text-center text-xs font-semibold focus:ring-2 focus:ring-blue-500"
                           placeholder="{{ $question->question_number }}"
                           @focus="setCurrentQuestion({{ $question->question_number }})">
                @endif
            </div>
        @endforeach
    </div>

    @if($unpositionedQuestions->isNotEmpty())
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($unpositionedQuestions as $question)
                <div id="question-block-{{ $question->question_number }}" class="rounded-xl border border-slate-200 p-4">
                    <label for="map-answer-list-{{ $question->id }}" class="mb-2 block text-sm font-medium">
                        <strong>{{ $question->question_number }}.</strong> {{ $question->prompt }}
                    </label>
                    @if(is_array($question->options) && count($question->options))
                        <select id="map-answer-list-{{ $question->id }}"
                                x-model="answers['{{ $question->id }}']"
                                @change="saveAnswer({{ $question->id }}, $event.target.value)"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Chọn đáp án</option>
                            @foreach($question->options as $option)
                                @php
                                    $optionKey = is_array($option) ? ($option['key'] ?? '') : $option;
                                    $optionText = is_array($option) ? ($option['text'] ?? '') : '';
                                @endphp
                                <option value="{{ $optionKey }}">{{ $optionKey }}{{ $optionText !== '' ? ' — '.$optionText : '' }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="map-answer-list-{{ $question->id }}" type="text"
                               x-model="answers['{{ $question->id }}']"
                               @input.debounce.300ms="saveAnswer({{ $question->id }}, answers['{{ $question->id }}'])"
                               class="ielts-numbered-answer w-full rounded-lg border border-slate-300 px-3 py-2 text-center text-sm"
                               placeholder="{{ $question->question_number }}"
                               aria-label="Câu {{ $question->question_number }}"
                               @focus="setCurrentQuestion({{ $question->question_number }})">
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
