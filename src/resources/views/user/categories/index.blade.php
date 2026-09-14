@extends('layouts.app')

@section('content')

<x-page-header 
    title="Danh Mục Bài Học"
    badge="Khám phá khoá học"
    description="Lựa chọn chứng chỉ hoặc mục tiêu học tập phù hợp để bắt đầu lộ trình làm chủ từ vựng của bạn."
    :breadcrumbs="['Danh mục' => '']" 
/>

<section id="categories" class="categories-section container mx-auto px-5 py-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Tất cả danh mục ({{ $categories->count() }})</h2>
            <p class="text-gray-500 mt-1 text-sm">Các bài học được phân loại từ cơ bản đến nâng cao</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($categories as $category)
            <a href="{{ route('categories.show', $category) }}"
                class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-blue-500 hover:shadow-xl flex flex-col justify-between min-h-[160px]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-500 block mb-1">Danh mục</span>
                        <h3 class="text-xl font-bold text-gray-800 group-hover:text-blue-600 transition-colors">
                            {{ $category->name }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-400 font-medium">
                            📁 {{ $category->topics_count }} {{ Str::plural('topic', $category->topics_count) }}
                        </p>
                    </div>
                    <div class="text-3xl bg-blue-50 p-3 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        📚
                    </div>
                </div>
                <div class="mt-6 text-sm font-semibold text-blue-600 flex items-center gap-1 opacity-0 translate-x-[-10px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                    Khám phá chủ đề <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                <p class="text-sm text-gray-500">Chưa có danh mục nào.</p>
            </div>
        @endforelse
    </div>
</section>

@endsection