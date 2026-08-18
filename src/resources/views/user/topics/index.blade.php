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

<section class="container mx-auto px-5 py-12">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Danh sách từ vựng</h2>
            <p class="text-sm text-gray-500 mt-1">Bấm vào thẻ hoặc biểu tượng để nghe phát âm (nếu có)</p>
        </div>
        <span class="px-3 py-1 bg-blue-50 text-blue-600 text-sm font-semibold rounded-full">
            {{ count($topic->vocabularies) }} Từ mới
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($topic->vocabularies as $vocab)
            
            <div class="group bg-white rounded-2xl border border-gray-150 p-6 shadow-sm hover:shadow-xl hover:border-blue-500 transition-all duration-300 flex flex-col justify-between">
                
                <div>
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <h3 class="text-2xl font-bold text-gray-900 tracking-tight group-hover:text-blue-600 transition-colors">
                            {{ $vocab['word'] }}
                        </h3>
                        
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $vocab['level'] === 'easy' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ ucfirst($vocab['level']) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-400 font-mono mb-4">
                        <span>{{ $vocab['pronunciation'] }}</span>
                        
                        <button class="text-gray-400 hover:text-blue-600 transition-colors focus:outline-none p-1 rounded-md hover:bg-gray-50" title="Nghe phát âm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C12.923 3.663 14 4.109 14 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50/70 rounded-xl p-3 border border-gray-100 mb-4">
                        <p class="text-gray-800 font-medium">
                            <span class="text-blue-500 mr-1.5">▪</span>{{ $vocab['meaning'] }}
                        </p>
                    </div>
                </div>

                @if(!empty($vocab['example']))
                    <div class="pt-3 border-t border-gray-100">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">Ví dụ:</span>
                        <p class="text-sm text-gray-600 italic leading-relaxed">
                            "{!! preg_replace('/(' . preg_quote($vocab['word'], '/') . ')/i', '<strong class="text-gray-900 not-italic font-bold">$1</strong>', $vocab['example']) !!}"
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

@endsection