@extends('layouts.app')

@section('content')

{{-- Gọi component và truyền dữ liệu động vào --}}
<x-page-header 
    :title="$topic->name"
    badge="Chủ đề bài học"
    :description="$topic->description ?? 'Học và làm chủ toàn bộ từ vựng cốt lõi của chủ đề này.'"
    :breadcrumbs="[
        $category->name => route('categories.show', $category->slug), 
        $topic->name => ''
    ]" 
/>

@php
    $isAuth = auth()->check();
    $totalVocabs = $topic->vocabularies->count();
    $masteredCount = $progressSummary['mastered'] ?? 0;
    $progressPercent = $totalVocabs > 0 ? round($masteredCount / $totalVocabs * 100) : 0;
@endphp

<section 
    class="container mx-auto px-5 py-12"
    x-data="vocabularyLearning(
        {{ json_encode($progressMap) }},
        {{ json_encode($progressSummary) }},
        '{{ csrf_token() }}',
        {{ $isAuth ? 'true' : 'false' }}
    )"
>
    {{-- Toast Notification --}}
    <div 
        x-show="toastMsg" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed bottom-6 right-6 z-50 bg-gray-900 text-white text-sm font-medium px-5 py-3 rounded-2xl shadow-xl"
        x-text="toastMsg"
        style="display: none;"
    ></div>

    {{-- Header: Tiêu đề + Progress Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Danh sách từ vựng</h2>
                <p class="text-sm text-gray-500 mt-1">Bấm 🔊 để nghe phát âm, gõ lại từ tiếng Anh vào ô bên dưới để xác nhận đã thuộc</p>
            </div>
            <span class="px-3.5 py-1.5 bg-blue-50 text-blue-700 text-sm font-semibold rounded-full border border-blue-100">
                {{ $totalVocabs }} Từ vựng
            </span>
        </div>

        @if($isAuth && $totalVocabs > 0)
        {{-- Progress bar tiến độ học --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div class="flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-1.5 text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
                        Đang học: <strong x-text="summary.learning" class="text-gray-700 ml-0.5">{{ $progressSummary['learning'] }}</strong>
                    </span>
                    <span class="flex items-center gap-1.5 text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        Đã thuộc: <strong x-text="summary.mastered" class="text-gray-700 ml-0.5">{{ $progressSummary['mastered'] }}</strong>
                    </span>
                    <span class="text-gray-400">/ {{ $totalVocabs }} từ</span>
                </div>
                <span class="text-sm font-bold text-emerald-600" x-text="progressPercent + '%'">{{ $progressPercent }}%</span>
            </div>
            <div class="relative w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                {{-- Thanh learning --}}
                <div 
                    class="absolute left-0 top-0 h-3 bg-amber-300 rounded-full transition-all duration-500"
                    :style="'width: ' + learnedPercent + '%'"
                    style="width: {{ $progressSummary['total'] > 0 ? round(($progressSummary['learning'] + $progressSummary['mastered']) / $progressSummary['total'] * 100) : 0 }}%"
                ></div>
                {{-- Thanh mastered (overlay) --}}
                <div 
                    class="absolute left-0 top-0 h-3 bg-emerald-500 rounded-full transition-all duration-500"
                    :style="'width: ' + progressPercent + '%'"
                    style="width: {{ $progressPercent }}%"
                ></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Vocabulary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($topic->vocabularies as $vocab)
            <div 
                x-data="{ 
                    inputVal: '', 
                    errorMsg: '', 
                    shake: false, 
                    isFocused: false,
                    showHint: false 
                }"
                class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-xl hover:border-blue-400 transition-all duration-300 flex flex-col justify-between overflow-hidden"
                :class="{
                    'border-emerald-300 bg-emerald-50/30': isMastered({{ $vocab->id }}),
                    'border-amber-200 bg-amber-50/20': isLearning({{ $vocab->id }}) && !isMastered({{ $vocab->id }}),
                    'is-blur-word': (isFocused || inputVal.length > 0) && !showHint && !isMastered({{ $vocab->id }})
                }"
            >
                {{-- Card Body --}}
                <div class="p-6">
                    {{-- Header: Từ + Level badge + Status badge --}}
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <button 
                            @click="speak('{{ addslashes($vocab->word) }}'); markReview({{ $vocab->id }})" 
                            class="text-left text-2xl font-bold text-gray-900 tracking-tight group-hover:text-blue-600 transition-colors focus:outline-none hover:underline flex items-center gap-2"
                            title="Bấm để nghe phát âm"
                        >
                            <span class="vocab-main-word">{{ $vocab->word }}</span>
                        </button>

                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            {{-- Level badge --}}
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $vocab->level === 'easy' ? 'bg-green-50 text-green-700 border border-green-200' : ($vocab->level === 'hard' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst($vocab->level ?? 'easy') }}
                            </span>

                            @if($isAuth)
                            {{-- Status badge (Alpine reactive) --}}
                            <span 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-all duration-200"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200': isMastered({{ $vocab->id }}),
                                    'bg-amber-50 text-amber-700 border-amber-200': isLearning({{ $vocab->id }}) && !isMastered({{ $vocab->id }}),
                                    'bg-gray-50 text-gray-400 border-gray-200': getStatus({{ $vocab->id }}) === 'new'
                                }"
                            >
                                <span x-show="isMastered({{ $vocab->id }})">✅ Đã thuộc</span>
                                <span x-show="isLearning({{ $vocab->id }}) && !isMastered({{ $vocab->id }})">📖 Đang học</span>
                                <span x-show="getStatus({{ $vocab->id }}) === 'new'">🆕 Mới</span>
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Pronunciation + Speaker --}}
                    <div class="flex items-center gap-2 text-sm text-gray-500 font-mono mb-4">
                        @if($vocab->pronunciation)
                            <span>{{ $vocab->pronunciation }}</span>
                        @endif
                        
                        <button 
                            @click="speak('{{ addslashes($vocab->word) }}'); markReview({{ $vocab->id }})"
                            class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-1.5 rounded-lg transition-colors focus:outline-none active:scale-95" 
                            title="Nghe phát âm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C12.923 3.663 14 4.109 14 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Meaning --}}
                    <div class="bg-gray-50/80 rounded-xl p-3.5 border border-gray-100 mb-4">
                        <p class="text-gray-800 font-medium text-sm sm:text-base leading-relaxed">
                            <span class="text-blue-500 mr-1.5 font-bold">▪</span>{{ $vocab->meaning }}
                        </p>
                    </div>

                    {{-- Example --}}
                    @if(!empty($vocab->example))
                        <div class="pt-3 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Ví dụ:</span>
                                <button 
                                    @click="speak('{{ addslashes($vocab->example) }}')" 
                                    class="text-xs text-gray-400 hover:text-blue-600 focus:outline-none" 
                                    title="Nghe toàn bộ câu ví dụ"
                                >
                                    🔊 Nghe câu
                                </button>
                            </div>
                            <p class="text-sm text-gray-600 italic leading-relaxed">
                                "{!! $vocab->highlighted_example !!}"
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Card Footer: Kiểm tra từ vựng bằng ô input (chỉ hiện khi đã login) --}}
                @if($isAuth)
                <div class="px-5 py-3 bg-gray-50/80 border-t border-gray-100">
                    {{-- Chưa thuộc: Ô input nhập từ tiếng Anh --}}
                    <div x-show="!isMastered({{ $vocab->id }})">
                        <form 
                            @submit.prevent="
                                const res = checkAndMaster({{ $vocab->id }}, inputVal, @js($vocab->word));
                                if (res.success) {
                                    inputVal = '';
                                    errorMsg = '';
                                    isFocused = false;
                                    showHint = false;
                                } else {
                                    errorMsg = res.message;
                                    shake = true;
                                    setTimeout(() => shake = false, 500);
                                }
                            "
                            class="space-y-1.5"
                        >
                            <div class="relative flex items-center" :class="{ 'animate-shake': shake }">
                                <input 
                                    type="text"
                                    x-model="inputVal"
                                    @focus="isFocused = true; markReview({{ $vocab->id }})"
                                    @blur="isFocused = false"
                                    @input="if (errorMsg) errorMsg = ''"
                                    placeholder="Gõ lại từ tiếng Anh..."
                                    autocomplete="off"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    :class="errorMsg 
                                        ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200 bg-rose-50/30' 
                                        : 'border-gray-200 focus:border-blue-500 focus:ring-blue-100 bg-white'"
                                    class="w-full text-xs sm:text-sm pl-3.5 pr-24 py-2 rounded-xl border focus:outline-none focus:ring-2 transition-all placeholder:text-gray-400 text-gray-800 font-medium"
                                />
                                <div class="absolute right-1.5 flex items-center gap-1">
                                    {{-- Nút giữ mắt để xem gợi ý khi bị che mờ --}}
                                    <button 
                                        type="button"
                                        x-show="(isFocused || inputVal.length > 0) && !isMastered({{ $vocab->id }})"
                                        @mousedown.prevent="showHint = true"
                                        @mouseup="showHint = false"
                                        @mouseleave="showHint = false"
                                        @touchstart.prevent="showHint = true"
                                        @touchend="showHint = false"
                                        class="p-1 text-gray-400 hover:text-blue-600 rounded-md transition-colors"
                                        title="Nhấn giữ để xem từ gợi ý"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    <button 
                                        type="submit"
                                        :disabled="!inputVal.trim() || loadingId === {{ $vocab->id }}"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition-all active:scale-95 shadow-sm"
                                        title="Kiểm tra từ vừa nhập"
                                    >
                                        <span x-show="loadingId !== {{ $vocab->id }}">Kiểm tra</span>
                                        <span x-show="loadingId === {{ $vocab->id }}">⏳</span>
                                    </button>
                                </div>
                            </div>
                            
                            {{-- Báo lỗi nếu nhập sai --}}
                            <p 
                                x-show="errorMsg" 
                                x-text="errorMsg" 
                                class="text-xs text-rose-600 font-medium flex items-center gap-1 pl-1"
                                style="display: none;"
                            ></p>
                        </form>
                    </div>

                    {{-- Đã thuộc: Trạng thái chúc mừng + Nút học lại --}}
                    <div 
                        x-show="isMastered({{ $vocab->id }})" 
                        class="flex items-center justify-between gap-2"
                        style="display: none;"
                    >
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-3 py-1.5 rounded-xl">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Đã thuộc từ này!</span>
                        </div>

                        <button
                            @click="
                                markReset({{ $vocab->id }});
                                inputVal = '';
                                errorMsg = '';
                                isFocused = false;
                                showHint = false;
                            "
                            :disabled="loadingId === {{ $vocab->id }}"
                            class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-blue-600 bg-white hover:bg-blue-50 border border-gray-200 hover:border-blue-200 px-3 py-1.5 rounded-xl transition-all active:scale-95 disabled:opacity-50 shadow-2xs"
                            title="Bấm để luyện gõ lại từ này"
                        >
                            <span x-show="loadingId !== {{ $vocab->id }}">🔄 Học lại</span>
                            <span x-show="loadingId === {{ $vocab->id }}">⏳</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                <span class="text-4xl">📝</span>
                <h3 class="mt-4 text-base font-semibold text-gray-900">Chưa có từ vựng nào</h3>
                <p class="mt-1 text-sm text-gray-500">Từ vựng cho chủ đề này đang được cập nhật liên tục.</p>
            </div>
        @endforelse
    </div>

    {{-- CTA: Đăng nhập để theo dõi tiến độ --}}
    @if(!$isAuth)
    <div class="mt-10 text-center bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-8">
        <span class="text-3xl mb-3 block">📊</span>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Theo dõi tiến độ học của bạn</h3>
        <p class="text-gray-500 text-sm mb-5">Đăng nhập để luyện gõ từ vựng, theo dõi tiến độ và xem thống kê học tập cá nhân.</p>
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl transition-all active:scale-95">
            Đăng nhập ngay →
        </a>
    </div>
    @endif
</section>

@endsection