@extends('layouts.app')

@section('title', 'Game Ghép Cặp Từ Vựng Tiếng Anh – Vocafy')
@section('meta_description', 'Luyện phản xạ từ vựng tiếng Anh qua minigame ghép cặp từ và nghĩa tiếng Việt tương ứng. Hỗ trợ TOEIC, IELTS, TOEFL và toàn bộ 3000+ từ vựng.')
@section('canonical', route('game.matching'))

@section('content')
<div 
    class="min-h-screen bg-slate-50/60 py-8 px-4 sm:px-6 lg:px-8 relative overflow-hidden"
    x-data="matchingGame({
        categories: {{ Js::from($categories) }},
        initialScope: '{{ $initialScope }}',
        selectedCategoryId: '{{ $selectedCategoryId }}',
        selectedTopicId: '{{ $selectedTopicId }}',
        csrfToken: '{{ csrf_token() }}',
        isAuth: {{ auth()->check() ? 'true' : 'false' }},
        autoStart: {{ request()->has('autostart') ? 'true' : 'false' }}
    })"
>
    {{-- Confetti Canvas for Victory --}}
    <canvas id="victory-confetti-canvas" class="fixed inset-0 pointer-events-none z-50 w-full h-full"></canvas>

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- 1. HERO / BANNER HEADER --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-violet-700 text-white p-6 sm:p-8 shadow-xl shadow-indigo-500/10">
            {{-- Floating Decorative Blurs --}}
            <div class="absolute -right-8 -top-8 w-60 h-60 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute left-1/3 -bottom-10 w-48 h-48 bg-blue-400/20 rounded-full blur-xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-semibold tracking-wide uppercase text-blue-100">
                        <span>🎮</span> Minigame Luyện Phản Xạ Từ Vựng
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
                        Vocabulary Match Master
                    </h1>
                    <p class="text-blue-100/90 text-sm sm:text-base max-w-2xl leading-relaxed">
                        Ghép đúng các cặp Từ tiếng Anh và Nghĩa tiếng Việt. Chọn cấu hình trò chơi bên dưới và nhấn Bắt Đầu để rèn luyện phản xạ!
                    </p>
                </div>

                {{-- HUD when in Active Game --}}
                <div 
                    x-show="gameState === 'playing'"
                    x-cloak
                    class="flex items-center gap-3 bg-white/10 backdrop-blur-md p-3.5 rounded-2xl border border-white/20 shadow-inner"
                >
                    {{-- Score --}}
                    <div class="text-center px-3 border-r border-white/20">
                        <span class="block text-[11px] font-medium text-blue-200 uppercase tracking-wider">Điểm số</span>
                        <span class="text-2xl font-black text-amber-300" x-text="score.toLocaleString()">0</span>
                    </div>

                    {{-- Timer --}}
                    <div class="text-center px-3 border-r border-white/20">
                        <span class="block text-[11px] font-medium text-blue-200 uppercase tracking-wider" x-text="gameMode === 'timeAttack' ? 'Còn lại' : 'Thời gian'">Thời gian</span>
                        <span 
                            class="text-2xl font-black tracking-wider"
                            :class="{ 'timer-warning text-red-300': gameMode === 'timeAttack' && timeAttackSeconds <= 10, 'text-white': !(gameMode === 'timeAttack' && timeAttackSeconds <= 10) }"
                            x-text="formattedTime"
                        >00:00</span>
                    </div>

                    {{-- Sound Toggle & Reset --}}
                    <div class="flex flex-col gap-1.5 pl-1">
                        <button 
                            @click="toggleMute()" 
                            class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all text-xs flex items-center justify-center"
                            :title="isMuted ? 'Bật âm thanh' : 'Tắt âm thanh'"
                        >
                            <span x-show="!isMuted">🔊</span>
                            <span x-show="isMuted" x-cloak>🔇</span>
                        </button>
                        <button 
                            @click="resetRound()" 
                            class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all text-xs flex items-center justify-center"
                            title="Xáo trộn lại lượt này"
                        >
                            🔄
                        </button>
                    </div>
                </div>

                {{-- Badge when in Lobby --}}
                <div x-show="gameState === 'lobby'" class="hidden md:flex flex-col items-end text-right">
                    <div class="px-4 py-2 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-xs text-blue-100 font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        Hơn {{ number_format($totalVocabsCount) }}+ từ vựng sẵn sàng
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             2. LOBBY / CONFIGURATION SCREEN (When gameState === 'lobby')
             ============================================================ --}}
        <div 
            x-show="gameState === 'lobby'"
            x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="space-y-6"
        >
            {{-- Main Setup Card --}}
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 space-y-7">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">⚙️</span>
                        Thiết Lập Trận Đấu
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Tùy chỉnh nguồn từ vựng, độ khó và chế độ thi đấu trước khi bước vào màn chơi.</p>
                </div>

                {{-- Step 1: Scope Selection --}}
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                        1. Chọn nguồn từ vựng
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Option 1: All Vocabs --}}
                        <button 
                            type="button"
                            @click="changeScope('all')"
                            class="p-4 rounded-2xl border-2 text-left transition-all relative overflow-hidden group"
                            :class="scope === 'all' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div class="text-2xl mb-2">🎲</div>
                            <h3 class="font-bold text-slate-900 text-sm group-hover:text-indigo-600 transition-colors">Toàn bộ từ vựng</h3>
                            <p class="text-xs text-slate-500 mt-1">Random bất kỳ từ hơn {{ number_format($totalVocabsCount) }} từ của Vocafy.</p>
                            <span x-show="scope === 'all'" class="absolute top-3 right-3 text-indigo-600 text-sm font-bold">✓</span>
                        </button>

                        {{-- Option 2: Category --}}
                        <button 
                            type="button"
                            @click="changeScope('category')"
                            class="p-4 rounded-2xl border-2 text-left transition-all relative overflow-hidden group"
                            :class="scope === 'category' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div class="text-2xl mb-2">📂</div>
                            <h3 class="font-bold text-slate-900 text-sm group-hover:text-indigo-600 transition-colors">Theo danh mục</h3>
                            <p class="text-xs text-slate-500 mt-1">Random từ vựng trong chứng chỉ TOEIC, IELTS, TOEFL...</p>
                            <span x-show="scope === 'category'" class="absolute top-3 right-3 text-indigo-600 text-sm font-bold">✓</span>
                        </button>

                        {{-- Option 3: Topic --}}
                        <button 
                            type="button"
                            @click="changeScope('topic')"
                            class="p-4 rounded-2xl border-2 text-left transition-all relative overflow-hidden group"
                            :class="scope === 'topic' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div class="text-2xl mb-2">🎯</div>
                            <h3 class="font-bold text-slate-900 text-sm group-hover:text-indigo-600 transition-colors">Theo chủ đề</h3>
                            <p class="text-xs text-slate-500 mt-1">Luyện tập sâu các từ vựng thuộc một topic cụ thể.</p>
                            <span x-show="scope === 'topic'" class="absolute top-3 right-3 text-indigo-600 text-sm font-bold">✓</span>
                        </button>
                    </div>

                    {{-- Dynamic Selectors for Category / Topic --}}
                    <div 
                        x-show="scope === 'category' || scope === 'topic'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="p-4 bg-slate-50 rounded-2xl border border-slate-200/70 grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3"
                    >
                        {{-- Category Dropdown --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Danh mục chứng chỉ:</label>
                            <select 
                                x-model="selectedCategoryId"
                                @change="syncCategorySelection($event.target.value)"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            >
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                                <template x-if="categories.length === 0">
                                    <option disabled>Chưa có danh mục nào có từ vựng</option>
                                </template>
                            </select>
                        </div>

                        {{-- Topic Dropdown (Only when scope === 'topic') --}}
                        <div x-show="scope === 'topic'">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Chủ đề bài học:</label>
                            <select 
                                x-model="selectedTopicId"
                                @change="syncTopicSelection($event.target.value)"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            >
                                <template x-for="top in currentTopics" :key="top.id">
                                    <option 
                                        :value="top.id" 
                                        x-text="top.vocabularies_count >= 4 ? top.name + ' (' + top.vocabularies_count + ' từ)' : top.name + ' (' + top.vocabularies_count + ' từ — ít quá!)'"
                                        :disabled="top.vocabularies_count < 4"
                                    ></option>
                                </template>
                                <template x-if="currentTopics.length === 0">
                                    <option disabled>Danh mục này chưa có chủ đề nào có từ vựng</option>
                                </template>
                            </select>
                        </div>

                        {{-- Warning: topic không đủ từ --}}
                        <template x-if="scope === 'topic' && selectedTopicId && currentTopics.length > 0">
                            <div 
                                x-show="currentTopicVocabCount < 4"
                                class="col-span-2 flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700"
                            >
                                <span class="text-base">⚠️</span>
                                <span>Chủ đề này chỉ có <strong x-text="currentTopicVocabCount"></strong> từ vựng — cần ít nhất <strong>4 từ</strong> để bắt đầu trò chơi. Hãy chọn chủ đề khác.</span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Step 2: Pairs Count (Difficulty) --}}
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                        2. Số lượng cặp thẻ mỗi lượt
                    </label>

                    <div class="grid grid-cols-3 gap-3">
                        <button 
                            type="button"
                            @click="changePairLimit(4)"
                            class="p-3.5 sm:p-4 rounded-2xl border-2 text-center transition-all"
                            :class="pairLimit === 4 ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                        >
                            <span class="block text-lg sm:text-xl font-extrabold">4 Cặp</span>
                            <span class="text-xs text-slate-500 mt-0.5 block">8 Thẻ (Khởi động)</span>
                        </button>

                        <button 
                            type="button"
                            @click="changePairLimit(6)"
                            class="p-3.5 sm:p-4 rounded-2xl border-2 text-center transition-all"
                            :class="pairLimit === 6 ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                        >
                            <span class="block text-lg sm:text-xl font-extrabold">6 Cặp</span>
                            <span class="text-xs text-slate-500 mt-0.5 block">12 Thẻ (Tiêu chuẩn)</span>
                        </button>

                        <button 
                            type="button"
                            @click="changePairLimit(8)"
                            class="p-3.5 sm:p-4 rounded-2xl border-2 text-center transition-all"
                            :class="pairLimit === 8 ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                        >
                            <span class="block text-lg sm:text-xl font-extrabold">8 Cặp</span>
                            <span class="text-xs text-slate-500 mt-0.5 block">16 Thẻ (Thử thách)</span>
                        </button>
                    </div>
                </div>

                {{-- Step 3: Game Mode --}}
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                        3. Chế độ thi đấu
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button 
                            type="button"
                            @click="changeGameMode('practice')"
                            class="p-4 rounded-2xl border-2 text-left transition-all relative group"
                            :class="gameMode === 'practice' ? 'border-blue-600 bg-blue-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl">🧘</div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Luyện tập tự do</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Bấm giờ tính thời gian, không giới hạn để ghi nhớ từ.</p>
                                </div>
                            </div>
                            <span x-show="gameMode === 'practice'" class="absolute top-3 right-3 text-blue-600 font-bold">✓</span>
                        </button>

                        <button 
                            type="button"
                            @click="changeGameMode('timeAttack')"
                            class="p-4 rounded-2xl border-2 text-left transition-all relative group"
                            :class="gameMode === 'timeAttack' ? 'border-rose-600 bg-rose-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl">⚡</div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Đua 60 giây (Time Attack)</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Ghép liên tục các lượt từ để phá kỷ lục điểm số!</p>
                                </div>
                            </div>
                            <span x-show="gameMode === 'timeAttack'" class="absolute top-3 right-3 text-rose-600 font-bold">✓</span>
                        </button>
                    </div>
                </div>

                {{-- Big Action Button: START GAME --}}
                <div class="pt-4 border-t border-slate-100">
                    <button 
                        type="button"
                        @click="if (!canStart) return; startGame()"
                        :disabled="!canStart"
                        class="ripple-btn w-full py-4 sm:py-5 text-white font-extrabold text-lg sm:text-xl rounded-2xl shadow-xl transition-all flex items-center justify-center gap-3 cursor-pointer"
                        :class="canStart 
                            ? 'bg-gradient-to-r from-indigo-600 via-blue-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 shadow-indigo-500/25 hover:scale-[1.01] active:scale-98' 
                            : 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none'"
                    >
                        <span x-show="canStart">🚀</span>
                        <span x-show="!canStart" x-cloak>⚠️</span>
                        <span x-show="canStart" x-cloak>BẮT ĐẦU TRÒ CHƠI</span>
                        <span x-show="!canStart" x-cloak>Chủ đề không đủ từ để chơi</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             3. ACTIVE PLAYING SCREEN (When gameState === 'playing')
             ============================================================ --}}
        <div 
            x-show="gameState === 'playing'"
            x-cloak
            class="space-y-5"
        >
            {{-- Quick In-Game Bar --}}
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    {{-- Return to Lobby Button --}}
                    <button 
                        type="button"
                        @click="goToLobby()"
                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm transition-colors flex items-center gap-1.5"
                    >
                        <span>⚙️</span> Đổi cấu hình
                    </button>

                    {{-- Current Scope Pill --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 font-semibold text-xs border border-indigo-100" x-text="currentScopeLabel"></span>
                </div>

                {{-- Mode & Pairs indicator --}}
                <div class="flex items-center gap-4 text-xs font-semibold text-slate-500">
                    <span>Số cặp: <strong class="text-slate-800" x-text="pairLimit"></strong></span>
                    <span>•</span>
                    <span>Chế độ: <strong class="text-slate-800" x-text="gameMode === 'timeAttack' ? 'Đua 60s' : 'Luyện tập'"></strong></span>
                </div>
            </div>

            {{-- Status Bar (Streak, Matched, Accuracy) --}}
            <div class="flex items-center justify-between flex-wrap gap-4 px-2">
                <div class="flex items-center gap-3 text-sm font-semibold">
                    {{-- Streak Badge --}}
                    <div x-show="streak >= 2" x-cloak class="combo-badge">
                        <span>🔥</span> Combo x<span x-text="streak"></span>!
                    </div>

                    <span class="text-slate-500 text-xs sm:text-sm">
                        Đã ghép: <strong class="text-slate-800" x-text="matchedPairs"></strong>/<span x-text="totalPairs"></span> cặp
                    </span>
                </div>

                <div class="flex items-center gap-4 text-xs sm:text-sm font-medium text-slate-500">
                    <span>Độ chính xác: <strong class="text-emerald-600" x-text="accuracy + '%'">100%</strong></span>
                    <span class="hidden sm:inline">|</span>
                    <span class="hidden sm:inline">Chuỗi cao nhất: <strong class="text-indigo-600" x-text="maxStreak">0</strong></span>
                </div>
            </div>

            {{-- Game Cards Grid Container --}}
            <div class="relative min-h-[360px]">
                {{-- Loading Overlay --}}
                <div 
                    x-show="isLoading" 
                    class="absolute inset-0 bg-white/70 backdrop-blur-xs rounded-3xl z-20 flex flex-col items-center justify-center gap-3"
                >
                    <div class="loader-ring"></div>
                    <p class="text-sm font-semibold text-slate-600">Đang chuẩn bị từ vựng...</p>
                </div>

                {{-- Grid of Cards --}}
                <div 
                    class="grid gap-3.5 sm:gap-4.5"
                    :class="{
                        'grid-cols-2 sm:grid-cols-4': pairLimit === 4,
                        'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4': pairLimit === 6,
                        'grid-cols-2 sm:grid-cols-4 lg:grid-cols-4': pairLimit === 8
                    }"
                >
                    <template x-for="card in cards" :key="card.uid">
                        <div 
                            @click="onCardClick(card)"
                            class="game-card"
                            :class="{
                                'type-word': card.type === 'word',
                                'type-meaning': card.type === 'meaning',
                                'selected': card.isSelected,
                                'matched': card.isMatched,
                                'fade-out': card.isFaded,
                                'mismatched': card.isMismatched
                            }"
                        >
                            {{-- Language Pill Indicator --}}
                            <span 
                                class="absolute top-2.5 left-3 text-[10px] font-bold px-1.5 py-0.5 rounded-md uppercase tracking-wider"
                                :class="card.type === 'word' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                                x-text="card.type === 'word' ? 'EN' : 'VN'"
                            ></span>

                            {{-- Speaker Button for English words --}}
                            <template x-if="card.type === 'word'">
                                <button 
                                    type="button"
                                    @click.stop="speakWord(card.text)"
                                    class="absolute top-2.5 right-3 p-1 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Phát âm từ này"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M11 5L6 9H2v6h4l5 4V5z"/>
                                    </svg>
                                </button>
                            </template>

                            {{-- Card Main Text --}}
                            <div class="my-auto px-1">
                                <span 
                                    class="font-extrabold leading-snug block"
                                    :class="card.type === 'word' ? 'text-base sm:text-lg text-slate-900' : 'text-sm sm:text-base text-slate-700 font-semibold'"
                                    x-text="card.text"
                                ></span>

                                {{-- Optional Pronunciation Subtext --}}
                                <template x-if="card.type === 'word' && card.subText">
                                    <span class="text-xs text-slate-400 font-mono mt-0.5 block" x-text="card.subText"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================
         4. VICTORY / ROUND COMPLETE MODAL
         ============================================================ --}}
    <div 
        x-show="showVictoryModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm victory-overlay"
        style="display: none;"
    >
        <div class="victory-card bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-100 text-center space-y-6 relative overflow-hidden">
            {{-- Top Badge --}}
            <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-tr from-amber-400 to-amber-200 flex items-center justify-center text-4xl shadow-lg shadow-amber-500/20">
                🏆
            </div>

            <div class="space-y-1.5">
                <h3 class="text-2xl font-black text-slate-900">
                    <span x-show="gameMode === 'practice'">Xuất Sắc! Hoàn Thành Màn Chơi</span>
                    <span x-show="gameMode === 'timeAttack'">Hết Giờ! Kết Quả Của Bạn</span>
                </h3>
                <p class="text-sm text-slate-500">
                    Bạn đã ghi nhớ rất tốt các cặp từ vựng này. Hãy tiếp tục duy trì phong độ!
                </p>
            </div>

            {{-- Summary Stats Grid --}}
            <div class="grid grid-cols-2 gap-3 py-2 border-y border-slate-100">
                <div class="bg-slate-50 p-3 rounded-2xl">
                    <span class="text-xs text-slate-400 font-medium block">Điểm đạt được</span>
                    <strong class="text-xl font-black text-indigo-600" x-text="score.toLocaleString()">0</strong>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl">
                    <span class="text-xs text-slate-400 font-medium block">Thời gian</span>
                    <strong class="text-xl font-black text-slate-800" x-text="formattedTime">00:00</strong>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl">
                    <span class="text-xs text-slate-400 font-medium block">Độ chính xác</span>
                    <strong class="text-xl font-black text-emerald-600" x-text="accuracy + '%'">100%</strong>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl">
                    <span class="text-xs text-slate-400 font-medium block">Combo cao nhất</span>
                    <strong class="text-xl font-black text-amber-500" x-text="'x' + maxStreak">x0</strong>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-2.5">
                <button 
                    @click="nextRound()"
                    class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center gap-2"
                >
                    <span>🚀</span> Vòng tiếp theo (Từ mới ngẫu nhiên)
                </button>

                <button 
                    @click="resetRound()"
                    class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-colors text-sm"
                >
                    Chơi lại vòng này
                </button>

                <button 
                    @click="goToLobby()"
                    class="w-full py-2.5 text-slate-500 hover:text-slate-800 font-medium text-xs transition-colors"
                >
                    ⚙️ Quay về màn hình thiết lập
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
