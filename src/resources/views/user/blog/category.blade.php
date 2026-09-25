@extends('layouts.app')

@section('title', 'Chuyên Mục: ' . $category->name . ' — Vocafy Blog')
@section('meta_description', $category->description ?? 'Tổng hợp các bài viết hữu ích thuộc chuyên mục ' . $category->name . ' trên Vocafy Blog.')
@section('canonical', route('blog.category', $category))

@section('content')

{{-- ==================== CATEGORY HEADER ==================== --}}
<section class="relative overflow-hidden bg-gradient-to-b from-blue-50/70 via-indigo-50/20 to-white pt-12 pb-10 border-b border-gray-100">
    <div class="container mx-auto px-5 relative z-10 max-w-5xl">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-gray-400 mb-4 flex-wrap">
            <a href="/" class="hover:text-blue-600 transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-blue-600 transition-colors">Blog</a>
            <span>/</span>
            <span class="text-gray-700 font-semibold">{{ $category->name }}</span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100/90 text-blue-700 mb-3 border border-blue-200">
                    📂 Chuyên mục bài viết
                </span>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                    {{ $category->name }}
                </h1>
                @if($category->description)
                    <p class="mt-3 text-base text-gray-600 max-w-2xl leading-relaxed">
                        {{ $category->description }}
                    </p>
                @endif
            </div>

            <div class="bg-white border border-gray-200/80 px-5 py-4 rounded-2xl shadow-xs self-start md:self-center">
                <span class="text-xs text-gray-400 block font-medium">Tổng số bài viết</span>
                <span class="text-2xl font-black text-blue-600">{{ $posts->total() }}</span>
            </div>
        </div>
    </div>
</section>

{{-- ==================== CATEGORIES PILLS ==================== --}}
<section class="border-b border-gray-100 bg-white sticky top-[65px] z-30 shadow-xs">
    <div class="container mx-auto px-5 py-4">
        <div class="flex items-center gap-2 overflow-x-auto scrollbar-none py-1">
            <a href="{{ route('blog.index') }}" 
               class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200/80 transition-all">
                Tất cả bài viết
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.category', $cat) }}" 
                   class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-1.5 {{ $category->id === $cat->id ? 'bg-blue-600 text-white font-semibold shadow-sm shadow-blue-500/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200/80' }}">
                    <span>{{ $cat->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $category->id === $cat->id ? 'bg-blue-700/60 text-white' : 'bg-gray-200 text-gray-600' }}">
                        {{ $cat->published_posts_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== POSTS GRID ==================== --}}
<section class="container mx-auto px-5 py-12">
    @if($posts->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($posts as $post)
                <article class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-xs blog-card-hover flex flex-col justify-between group">
                    <div>
                        {{-- Thumbnail --}}
                        <a href="{{ route('blog.show', $post) }}" class="block relative aspect-16/10 overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600">
                            @if($post->thumbnail)
                                <img src="{{ asset('storage/' . $post->thumbnail) }}" 
                                     alt="{{ $post->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-white p-6 text-center">
                                    <span class="text-4xl mb-2">📚</span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-100">Vocafy Blog</span>
                                </div>
                            @endif
                        </a>

                        {{-- Body --}}
                        <div class="p-6">
                            <div class="flex items-center gap-2 text-xs text-gray-400 mb-2.5 font-medium">
                                <span>{{ $post->published_at ? $post->published_at->format('d/m/Y') : '' }}</span>
                                <span>•</span>
                                <span>⏱️ {{ $post->reading_time }} phút đọc</span>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 leading-snug mb-3">
                                <a href="{{ route('blog.show', $post) }}">
                                    {{ $post->title }}
                                </a>
                            </h3>

                            <p class="text-gray-500 text-sm line-clamp-3 leading-relaxed">
                                {{ $post->excerpt }}
                            </p>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 pb-6 pt-2 flex items-center justify-between border-t border-gray-100/80 text-xs text-gray-400">
                        <span class="font-medium text-gray-600">
                            ✍️ {{ $post->author->name ?? 'Vocafy' }}
                        </span>
                        <span>
                            👁️ {{ number_format($post->views_count) }} lượt xem
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    @else
        <div class="text-center py-16 bg-white rounded-3xl border border-dashed border-gray-200 p-8">
            <span class="text-5xl block mb-3">📝</span>
            <h3 class="text-lg font-bold text-gray-800 mb-1">Chưa có bài viết nào trong chuyên mục này</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
                Chuyên mục "{{ $category->name }}" hiện đang được cập nhật thêm các nội dung hữu ích. Vui lòng quay lại sau!
            </p>
            <a href="{{ route('blog.index') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl inline-block transition-all shadow-sm">
                Xem tất cả bài viết khác
            </a>
        </div>
    @endif
</section>

@endsection
