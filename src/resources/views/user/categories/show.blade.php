@extends('layouts.app')

@section('content')

{{-- Gọi component và truyền dữ liệu động vào --}}
<x-page-header 
    :title="$category->name"
    badge="Danh mục chủ đề"
    description="Khám phá và làm chủ từ vựng thông qua các chủ đề nhỏ được thiết kế khoa học bên dưới."
    :breadcrumbs="[$category->name => '']" 
/>
<!-- TOPICS LIST SECTION -->
<section class="container mx-auto px-5 py-12">
    {{-- Header: Tiêu đề + Thống kê tiến độ tổng của danh mục --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    Danh sách chủ đề ({{ $category->topics->count() }})
                </h2>
                <p class="text-gray-500 text-sm mt-1">Lựa chọn chủ đề để bắt đầu luyện tập và làm chủ từ vựng</p>
            </div>
            
            @if(auth()->check() && $categoryProgress && $categoryProgress['total'] > 0)
                <span class="px-4 py-2 bg-emerald-50 text-emerald-700 text-sm font-bold rounded-2xl border border-emerald-200/80 shadow-2xs flex items-center gap-1.5">
                    <span>🏆</span> Đã thuộc: {{ $categoryProgress['mastered'] }}/{{ $categoryProgress['total'] }} từ ({{ $categoryProgress['percent'] }}%)
                </span>
            @endif
        </div>

        @if(auth()->check() && $categoryProgress && $categoryProgress['total'] > 0)
        {{-- Progress Bar tổng của Category --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2.5 flex-wrap gap-2 text-sm">
                <div class="flex items-center gap-4 text-xs sm:text-sm">
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
                        Đang học: <strong class="text-gray-800 ml-0.5">{{ $categoryProgress['learning'] }}</strong>
                    </span>
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        Đã thuộc: <strong class="text-gray-800 ml-0.5">{{ $categoryProgress['mastered'] }}</strong>
                    </span>
                    <span class="text-gray-400">/ {{ $categoryProgress['total'] }} từ</span>
                </div>
                <span class="font-bold text-emerald-600">{{ $categoryProgress['percent'] }}% hoàn thành</span>
            </div>
            <div class="relative w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div 
                    class="absolute left-0 top-0 h-full bg-amber-300 rounded-full transition-all duration-500" 
                    style="width: {{ round((($categoryProgress['learning'] + $categoryProgress['mastered']) / $categoryProgress['total']) * 100) }}%"
                ></div>
                <div 
                    class="absolute left-0 top-0 h-full bg-emerald-500 rounded-full transition-all duration-500" 
                    style="width: {{ $categoryProgress['percent'] }}%"
                ></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Grid danh sách topics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($category->topics as $topic)
            @php
                $isAuth = auth()->check();
                $total = $topic->vocabularies_count;
                $mastered = $topic->mastered_vocabularies_count ?? 0;
                $learning = $topic->learning_vocabularies_count ?? 0;
                $percent = $total > 0 ? round(($mastered / $total) * 100) : 0;
                $learnedPercent = $total > 0 ? round((($mastered + $learning) / $total) * 100) : 0;
                $isCompleted = ($mastered === $total && $total > 0);
                $isLearning = ($mastered > 0 || $learning > 0);
            @endphp
            <div class="group bg-white rounded-2xl border transition-all duration-300 p-6 shadow-sm hover:shadow-xl flex flex-col justify-between min-h-[220px]
                {{ $isCompleted ? 'border-emerald-300 bg-emerald-50/15 hover:border-emerald-400' : ($isLearning ? 'border-amber-200 bg-amber-50/10 hover:border-blue-400' : 'border-gray-200 hover:border-blue-500') }}">
                <div>
                    {{-- Header của thẻ Topic: Số thứ tự + Badge tiến độ --}}
                    <div class="flex items-center justify-between mb-3 gap-2">
                        <span class="text-xs font-semibold text-gray-400">Topic #{{ $loop->iteration }}</span>
                        
                        @if($isAuth)
                            @if($isCompleted)
                                <span class="inline-flex items-center gap-1 py-0.5 px-2.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> ✅ Đã hoàn thành
                                </span>
                            @elseif($isLearning)
                                <span class="inline-flex items-center gap-1 py-0.5 px-2.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> 📖 Đang học ({{ $mastered }}/{{ $total }})
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-xs font-medium bg-gray-50 text-gray-400 border border-gray-200">
                                    🆕 Chưa học
                                </span>
                            @endif
                        @endif
                    </div>

                    {{-- Tên chủ đề --}}
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                        <a href="{{ route('topics.index', ['category' => $category->slug, 'topic' => $topic->slug]) }}">
                            {{ $topic->name }}
                        </a>
                    </h3>

                    {{-- Mô tả --}}
                    <p class="text-gray-500 text-sm mt-1.5 leading-relaxed line-clamp-2">
                        {{ $topic->description ?? 'Học các từ vựng cốt lõi và cách ứng dụng vào ngữ cảnh giao tiếp thực tế.' }}
                    </p>

                    {{-- Mini Progress bar cho mỗi topic (khi đã login và có từ vựng) --}}
                    @if($isAuth && $total > 0)
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-gray-400 font-medium">Tiến độ từ vựng</span>
                            <span class="font-bold {{ $isCompleted ? 'text-emerald-600' : ($isLearning ? 'text-amber-600' : 'text-gray-400') }}">
                                {{ $mastered }}/{{ $total }} ({{ $percent }}%)
                            </span>
                        </div>
                        <div class="relative w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="absolute left-0 top-0 h-full bg-amber-300 rounded-full transition-all duration-300" style="width: {{ $learnedPercent }}%"></div>
                            <div class="absolute left-0 top-0 h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Footer của thẻ Topic --}}
                <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500 font-medium">
                        <span>📝</span>
                        <span>{{ $topic->vocabularies_count }} từ vựng</span>
                    </div>

                    <a href="{{ route('topics.index', ['category' => $category->slug, 'topic' => $topic->slug]) }}" 
                       class="inline-flex items-center gap-1 text-xs sm:text-sm font-semibold {{ $isCompleted ? 'text-emerald-600 hover:text-emerald-700' : 'text-blue-600 hover:text-blue-700' }} group-hover:translate-x-0.5 transition-all">
                        @if($isCompleted)
                            Ôn tập lại &rarr;
                        @elseif($isLearning)
                            Tiếp tục học &rarr;
                        @else
                            Bắt đầu học &rarr;
                        @endif
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                <p class="text-sm text-gray-500">Nội dung học tập cho danh mục này đang được cập nhật.</p>
            </div>
        @endforelse
    </div>
</section>

@endsection