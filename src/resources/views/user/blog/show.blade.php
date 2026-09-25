@extends('layouts.app')

@section('title', ($post->meta_title ?: $post->title) . ' — Vocafy Blog')
@section('meta_description', $post->meta_description ?: ($post->excerpt ?: Str::limit(strip_tags($post->content), 150)))
@section('canonical', route('blog.show', $post))
@section('og_image', $post->thumbnail ? asset('storage/' . $post->thumbnail) : asset('images/og-image.jpg'))
@section('og_type', 'article')

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": "{{ addslashes($post->title) }}",
    "description": "{{ addslashes($post->excerpt ?: Str::limit(strip_tags($post->content), 150)) }}",
    "image": "{{ $post->thumbnail ? asset('storage/' . $post->thumbnail) : asset('images/og-image.jpg') }}",
    "datePublished": "{{ $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "author": {
        "@type": "Person",
        "name": "{{ addslashes($post->author->name ?? 'Vocafy Team') }}"
    },
    "publisher": {
        "@type": "Organization",
        "name": "{{ config('app.name', 'Vocafy') }}",
        "logo": {
            "@type": "ImageObject",
            "url": "{{ asset('images/logo.png') }}"
        }
    },
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ route('blog.show', $post) }}"
    }
}
</script>
@endpush

@section('content')

{{-- ==================== ARTICLE HERO & BREADCRUMBS ==================== --}}
<div class="bg-gradient-to-b from-blue-50/60 to-white border-b border-gray-100 py-8">
    <div class="container mx-auto px-5 max-w-7xl">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-gray-400 mb-6 flex-wrap">
            <a href="/" class="hover:text-blue-600 transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-blue-600 transition-colors">Blog</a>
            <span>/</span>
            <a href="{{ route('blog.category', $post->category) }}" class="hover:text-blue-600 transition-colors">{{ $post->category->name }}</a>
            <span>/</span>
            <span class="text-gray-700 font-medium truncate max-w-xs">{{ $post->title }}</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-5 py-10 max-w-7xl">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

        {{-- ==================== MAIN CONTENT (8 COLS) ==================== --}}
        <main class="lg:col-span-8">
            <article class="bg-white rounded-3xl border border-gray-200/80 p-6 sm:p-10 shadow-xs">
                {{-- Category Pill --}}
                <div class="mb-4">
                    <a href="{{ route('blog.category', $post->category) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors border border-blue-100">
                        📁 {{ $post->category->name }}
                    </a>
                </div>

                {{-- Post Title --}}
                <h1 class="text-2xl sm:text-4xl font-black text-gray-900 leading-tight tracking-tight mb-6">
                    {{ $post->title }}
                </h1>

                {{-- Post Metadata Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-4 py-4 border-y border-gray-100 mb-8 text-xs sm:text-sm text-gray-500">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center shadow-xs">
                            {{ mb_substr($post->author->name ?? 'V', 0, 1) }}
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 block">{{ $post->author->name ?? 'Vocafy Team' }}</span>
                            <span class="text-xs text-gray-400">
                                Xuất bản: {{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-medium text-gray-400">
                        <span class="flex items-center gap-1">
                            ⏱️ {{ $post->reading_time }} phút đọc
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1">
                            👁️ {{ number_format($post->views_count) }} lượt xem
                        </span>
                    </div>
                </div>

                {{-- Excerpt Highlight --}}
                @if($post->excerpt)
                    <div class="p-5 rounded-2xl bg-blue-50/70 border-l-4 border-blue-600 text-blue-950 font-medium text-base sm:text-lg leading-relaxed mb-8">
                        {{ $post->excerpt }}
                    </div>
                @endif

                {{-- Featured Image --}}
                @if($post->thumbnail)
                    <div class="mb-8 rounded-2xl overflow-hidden shadow-sm">
                        <img src="{{ asset('storage/' . $post->thumbnail) }}" 
                             alt="{{ $post->title }}" 
                             class="w-full max-h-[460px] object-cover">
                    </div>
                @endif

                {{-- Post Content --}}
                <div class="blog-content">
                    {!! $post->content !!}
                </div>

                {{-- Social Share & Copy Link --}}
                <div class="mt-12 pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4"
                     x-data="{ copied: false }">
                    <span class="text-sm font-bold text-gray-700">Chia sẻ bài viết này:</span>
                    <div class="flex items-center gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                            Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                            X (Twitter)
                        </a>
                        <button @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2500)"
                                type="button"
                                class="px-3.5 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-semibold flex items-center gap-1.5 transition-all">
                            <span x-show="!copied">📋 Sao chép link</span>
                            <span x-show="copied" x-cloak class="text-green-600 font-bold">✓ Đã sao chép!</span>
                        </button>
                    </div>
                </div>

                {{-- Author Bio Card --}}
                <div class="mt-10 p-6 rounded-2xl bg-gray-50 border border-gray-100 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-xl shadow-xs shrink-0">
                        {{ mb_substr($post->author->name ?? 'V', 0, 1) }}
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-600">Về tác giả</span>
                        <h4 class="text-base font-bold text-gray-900">{{ $post->author->name ?? 'Vocafy Team' }}</h4>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">
                            Biên tập viên nội dung tại Vocafy, đam mê chia sẻ phương pháp học tiếng Anh hiện đại và hiệu quả.
                        </p>
                    </div>
                </div>
            </article>

            {{-- ==================== RELATED POSTS ==================== --}}
            @if($relatedPosts->count() > 0)
                <div class="mt-12">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <span>📖</span> Bài viết liên quan cùng chuyên mục
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        @foreach($relatedPosts as $related)
                            <a href="{{ route('blog.show', $related) }}" 
                               class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs blog-card-hover flex flex-col justify-between group">
                                <div>
                                    <div class="relative aspect-video rounded-xl overflow-hidden bg-gradient-to-br from-blue-400 to-indigo-500 mb-3">
                                        @if($related->thumbnail)
                                            <img src="{{ asset('storage/' . $related->thumbnail) }}" alt="{{ $related->title }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-white text-2xl">📝</div>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 leading-snug">
                                        {{ $related->title }}
                                    </h4>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                    <span>{{ $related->published_at ? $related->published_at->format('d/m/Y') : '' }}</span>
                                    <span>⏱️ {{ $related->reading_time }}m</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </main>

        {{-- ==================== SIDEBAR (4 COLS) ==================== --}}
        <aside class="lg:col-span-4 space-y-8">
            {{-- Search Box --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Tìm kiếm</h3>
                <form action="{{ route('blog.index') }}" method="GET">
                    <div class="relative flex items-center">
                        <input type="text" 
                               name="q" 
                               placeholder="Tìm bài viết..." 
                               class="w-full rounded-xl border border-gray-200 text-sm px-4 py-2.5 pr-10 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                        <button type="submit" class="absolute right-3 text-gray-400 hover:text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Categories Widget --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Chuyên mục Blog</h3>
                <ul class="space-y-2.5">
                    @foreach($categories as $cat)
                        <li>
                            <a href="{{ route('blog.category', $cat) }}" 
                               class="flex items-center justify-between py-2 px-3 rounded-xl text-sm transition-colors {{ $post->post_category_id === $cat->id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
                                <span>{{ $cat->name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $post->post_category_id === $cat->id ? 'bg-blue-200/70 text-blue-800' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $cat->published_posts_count }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Popular Articles --}}
            @if(isset($popularPosts) && $popularPosts->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-xs">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Bài viết xem nhiều</h3>
                    <div class="space-y-4">
                        @foreach($popularPosts as $pop)
                            <a href="{{ route('blog.show', $pop) }}" class="flex items-center gap-3.5 group">
                                <div class="w-16 h-14 rounded-xl overflow-hidden bg-gradient-to-br from-blue-400 to-indigo-500 shrink-0">
                                    @if($pop->thumbnail)
                                        <img src="{{ asset('storage/' . $pop->thumbnail) }}" alt="{{ $pop->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white text-base">📚</div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-blue-600 transition-colors line-clamp-2 leading-snug">
                                        {{ $pop->title }}
                                    </h4>
                                    <span class="text-[11px] text-gray-400 mt-1 block">
                                        👁️ {{ number_format($pop->views_count) }} lượt xem
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Call To Action Widget --}}
            <div class="rounded-3xl bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-700 text-white p-7 text-center shadow-lg shadow-blue-500/15">
                <span class="text-4xl block mb-3">🚀</span>
                <h3 class="text-lg font-black mb-2">Bắt đầu học từ vựng ngay!</h3>
                <p class="text-xs text-blue-100 leading-relaxed mb-5">
                    Hơn 3000+ từ vựng TOEIC & IELTS được chia nhỏ theo phương pháp ghi nhớ ngắt quãng thông minh.
                </p>
                <a href="{{ route('categories.index') }}" 
                   class="inline-block w-full py-3 px-4 rounded-xl bg-white text-blue-600 font-bold text-sm hover:bg-blue-50 transition-all shadow-md active:scale-95">
                    Khám phá kho từ vựng
                </a>
            </div>
        </aside>

    </div>
</div>

@endsection
