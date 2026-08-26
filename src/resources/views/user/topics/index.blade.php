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

<section class="container mx-auto px-5 py-12" x-data="vocabularyPlayer()">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Danh sách từ vựng</h2>
            <p class="text-sm text-gray-500 mt-1">Bấm vào biểu tượng loa 🔊 hoặc tên từ để nghe phát âm chuẩn bản xứ</p>
        </div>
        <span class="px-3.5 py-1.5 bg-blue-50 text-blue-700 text-sm font-semibold rounded-full border border-blue-100">
            {{ $topic->vocabularies->count() }} Từ vựng
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($topic->vocabularies as $vocab)
            <div class="group bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-xl hover:border-blue-500 transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <button @click="speak('{{ addslashes($vocab->word) }}')" 
                                class="text-left text-2xl font-bold text-gray-900 tracking-tight group-hover:text-blue-600 transition-colors focus:outline-none hover:underline flex items-center gap-2"
                                title="Bấm để nghe phát âm">
                            <span>{{ $vocab->word }}</span>
                        </button>
                        
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $vocab->level === 'easy' ? 'bg-green-50 text-green-700 border border-green-200' : ($vocab->level === 'hard' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                            {{ ucfirst($vocab->level ?? 'easy') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-500 font-mono mb-4">
                        @if($vocab->pronunciation)
                            <span>{{ $vocab->pronunciation }}</span>
                        @endif
                        
                        <button @click="speak('{{ addslashes($vocab->word) }}')"
                                class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-1.5 rounded-lg transition-colors focus:outline-none active:scale-95" 
                                title="Nghe phát âm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C12.923 3.663 14 4.109 14 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50/80 rounded-xl p-3.5 border border-gray-100 mb-4">
                        <p class="text-gray-800 font-medium text-sm sm:text-base leading-relaxed">
                            <span class="text-blue-500 mr-1.5 font-bold">▪</span>{{ $vocab->meaning }}
                        </p>
                    </div>
                </div>

                @if(!empty($vocab->example))
                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Ví dụ:</span>
                            <button @click="speak('{{ addslashes($vocab->example) }}')" class="text-xs text-gray-400 hover:text-blue-600 focus:outline-none" title="Nghe toàn bộ câu ví dụ">
                                🔊 Nghe câu
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 italic leading-relaxed">
                            "{!! $vocab->highlighted_example !!}"
                        </p>
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
</section>

<script>
    function vocabularyPlayer() {
        return {
            speak(text) {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = 'en-US';
                    utterance.rate = 0.9;
                    window.speechSynthesis.speak(utterance);
                } else {
                    alert('Trình duyệt của bạn không hỗ trợ phát âm tự động.');
                }
            }
        };
    }
</script>

@endsection