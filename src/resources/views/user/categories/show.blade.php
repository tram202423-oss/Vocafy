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
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">
            Danh sách chủ đề ({{ $category->topics->count() }})
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($category->topics as $topic)
            <div class="group bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-xl hover:border-blue-500 transition-all duration-300 flex flex-col justify-between min-h-[180px]">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-400">Topic #{{ $loop->iteration }}</span>
                        @if($loop->first)
                            <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Đang học
                            </span>
                        @endif
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                        {{ $topic->name }}
                    </h3>

                    <p class="text-gray-500 text-sm mt-1.5 leading-relaxed">
                        {{ $topic->description ?? 'Học các từ vựng cốt lõi và cách ứng dụng vào ngữ cảnh giao tiếp thực tế.' }}
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-sm text-gray-500 font-medium">
                        <span>📝</span>
                        <span>{{ $topic->vocabularies_count }} từ vựng</span>
                    </div>

                    <a href="{{ route('topics.index', ['category' => $category->slug, 'topic' => $topic->slug]) }}" 
                       class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 group-hover:text-blue-700">
                        Bắt đầu học &rarr;
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