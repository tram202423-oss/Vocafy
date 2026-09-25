@extends('layouts.app')

@section('title', 'Blog Chia Sẻ Kinh Nghiệm & Mẹo Học Tiếng Anh')
@section('meta_description', 'Khám phá các bài viết chia sẻ phương pháp học từ vựng hiệu quả, bí quyết luyện thi TOEIC, IELTS và kiến thức ngữ pháp tiếng Anh bổ ích từ Vocafy.')
@section('canonical', route('blog.index'))

@section('content')

{{-- ==================== HERO SECTION ==================== --}}
<section class="relative overflow-hidden bg-gradient-to-b from-blue-50/70 via-indigo-50/30 to-white pt-14 pb-12 border-b border-gray-100">
    <div class="hero-orb orb-1 w-96 h-96 bg-blue-200/30 -top-20 -right-20 pointer-events-none blur-3xl rounded-full absolute"></div>
    <div class="hero-orb orb-2 w-80 h-80 bg-indigo-200/25 -bottom-20 -left-20 pointer-events-none blur-3xl rounded-full absolute"></div>

    <div class="container mx-auto px-5 relative z-10 max-w-5xl text-center">
        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-blue-100/80 text-blue-700 mb-4 border border-blue-200/60 shadow-sm">
            <span>✨</span> Vocafy Knowledge Hub
        </span>
        <h1 class="text-3xl sm:text-5xl font-black text-gray-900 tracking-tight leading-tight">
            Góc Chia Sẻ & Mẹo Học Tiếng Anh
        </h1>
        <p class="mt-4 text-base sm:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
            Nâng tầm phương pháp học tập với những bài viết chất lượng về ghi nhớ từ vựng, chiến thuật làm bài thi và ngữ pháp ứng dụng.
        </p>

        {{-- Thanh tìm kiếm bài viết --}}
        <form action="{{ route('blog.index') }}" method="GET" class="mt-8 max-w-xl mx-auto">
            <div class="relative flex items-center shadow-md rounded-2xl bg-white border border-gray-200/80 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 transition-all p-1.5">
                <div class="pl-3.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       name="q" 
                       value="{{ $search ?? '' }}" 
                       placeholder="Tìm kiếm bài viết, chủ đề, mẹo học..." 
                       class="w-full border-0 focus:ring-0 text-sm text-gray-800 placeholder-gray-400 px-3 py-2 bg-transparent">
                @if(!empty($search))
                    <a href="{{ route('blog.index') }}" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1">Xóa</a>
                @endif
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm shadow-blue-500/20 active:scale-95">
                    Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</section>

{{-- ==================== CATEGORIES PILLS ==================== --}}
<section class="border-b border-gray-100 bg-white sticky top-[65px] z-30 shadow-xs">
    <div class="container mx-auto px-5 py-4">
        <div class="flex items-center gap-2 overflow-x-auto scrollbar-none py-1">
            <a href="{{ route('blog.index') }}" 
               class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ empty($search) && !isset($category) ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/25' : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80' }}">
                Tất cả bài viết
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.category', $cat) }}" 
                   class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-1.5 {{ (isset($category) && $category->id === $cat->id) ? 'bg-blue-600 text-white font-semibold shadow-sm shadow-blue-500/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200/80' }}">
                    <span>{{ $cat->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ (isset($category) && $category->id === $cat->id) ? 'bg-blue-700/60 text-white' : 'bg-gray-200 text-gray-600' }}">
                        {{ $cat->published_posts_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== MAIN BLOG CONTENT ==================== --}}
<section class="container mx-auto px-5 py-10 lg:py-14">

    @if(!empty($search))
        <div class="mb-8 p-4 rounded-xl bg-blue-50/70 border border-blue-100 flex items-center justify-between">
            <p class="text-sm text-blue-900 font-medium">
                Kết quả tìm kiếm cho từ khóa: <strong class="font-bold text-blue-700">"{{ $search }}"</strong> ({{ $posts->total() }} bài viết)
            </p>
            <a href="{{ route('blog.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                Xem tất cả bài viết &rarr;
            </a>
        </div>
    @endif

    {{-- ==================== FEATURED POST ==================== --}}
    @if(isset($featuredPost) && $featuredPost)
        <div class="mb-14">
            <div class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-3 flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-blue-600 animate-ping"></span>
                Bài viết tiêu điểm
            </div>

            <article class="bg-white rounded-3xl border border-gray-200/80 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 grid grid-cols-1 lg:grid-cols-12 group">
                <div class="lg:col-span-7 relative overflow-hidden bg-gradient-to-tr from-blue-600 to-indigo-700 min-h-[260px] sm:min-h-[340px]">
                    @if($featuredPost->thumbnail)
                        <img src="{{ asset('storage/' . $featuredPost->thumbnail) }}" 
                             alt="{{ $featuredPost->title }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center p-8 text-white text-center">
                            <span class="text-6xl mb-3">📖</span>
                            <span class="text-lg font-bold tracking-wider uppercase opacity-90">Vocafy Magazine</span>
                            <span class="text-xs text-blue-100 mt-1">{{ $featuredPost->category->name }}</span>
                        </div>
                    @endif
                    <div class="absolute top-4 left-4">
                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-white/95 text-blue-700 shadow-md backdrop-blur-sm">
                            {{ $featuredPost->category->name }}
                        </span>
                    </div>
                </div>

                <div class="lg:col-span-5 p-6 sm:p-8 lg:p-10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-3 text-xs text-gray-400 mb-3 font-medium">
                            <span>{{ $featuredPost->published_at ? $featuredPost->published_at->format('d/m/Y') : '' }}</span>
                            <span>•</span>
                            <span>⏱️ {{ $featuredPost->reading_time }} phút đọc</span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 group-hover:text-blue-600 transition-colors leading-tight mb-4">
                            <a href="{{ route('blog.show', $featuredPost) }}">
                                {{ $featuredPost->title }}
                            </a>
                        </h2>

                        <p class="text-gray-600 text-sm sm:text-base leading-relaxed line-clamp-3 mb-6">
                            {{ $featuredPost->excerpt }}
                        </p>
                    </div>

                    <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm">
                                {{ mb_substr($featuredPost->author->name ?? 'V', 0, 1) }}
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-800 block">{{ $featuredPost->author->name ?? 'Vocafy Team' }}</span>
                                <span class="text-[11px] text-gray-400">Tác giả</span>
                            </div>
                        </div>

                        <a href="{{ route('blog.show', $featuredPost) }}" 
                           class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-600 hover:text-blue-700 transition-colors">
                            Đọc bài viết <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    @endif

    {{-- ==================== POSTS GRID ==================== --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">
            {{ isset($featuredPost) ? 'Các bài viết mới nhất' : 'Tất cả bài viết' }}
        </h2>
        <p class="text-sm text-gray-500">Cập nhật liên tục những kiến thức và mẹo học tiếng Anh hay nhất</p>
    </div>

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
                            <div class="absolute top-3 left-3">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white/95 text-blue-700 shadow-sm backdrop-blur-xs">
                                    {{ $post->category->name }}
                                </span>
                            </div>
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
            <span class="text-5xl block mb-3">🔍</span>
            <h3 class="text-lg font-bold text-gray-800 mb-1">Không tìm thấy bài viết nào</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
                @if(!empty($search))
                    Không có bài viết nào phù hợp với từ khóa "{{ $search }}". Vui lòng thử tìm kiếm với từ khóa khác.
                @else
                    Hiện tại chưa có bài viết nào được đăng trong mục này. Vui lòng quay lại sau!
                @endif
            </p>
            <a href="{{ route('blog.index') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl inline-block transition-all shadow-sm">
                Quay lại tất cả bài viết
            </a>
        </div>
    @endif

</section>

@endsection
