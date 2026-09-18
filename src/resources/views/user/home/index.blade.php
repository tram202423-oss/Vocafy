@extends('layouts.app')

@section('title', 'Học Từ Vựng Tiếng Anh Online – TOEIC, IELTS, TOEFL')
@section('meta_description', 'Vocafy giúp bạn làm chủ 3000+ từ vựng tiếng Anh theo chứng chỉ TOEIC, IELTS, TOEFL và các chủ đề thực tế. Phương pháp lặp lại ngắt quãng khoa học – miễn phí.')
@section('canonical', route('home'))
@section('og_type', 'website')

@push('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Vocafy",
  "url": "{{ route('home') }}",
  "description": "Nền tảng học từ vựng tiếng Anh thông minh – TOEIC, IELTS, TOEFL",
  "publisher": {
    "@type": "Organization",
    "name": "Vocafy",
    "url": "{{ route('home') }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('vocafy.ico') }}"
    }
  }
}
</script>
@endpush

@section('content')

<!-- 1. HERO SECTION -->
<section class="hero-section relative overflow-hidden bg-gradient-to-b from-blue-50/60 to-white pt-20 pb-14">
    {{-- Floating Orbs --}}
    <div class="hero-orb orb-1 w-72 h-72 bg-blue-200/40 top-[-40px] left-[-60px]"></div>
    <div class="hero-orb orb-2 w-56 h-56 bg-indigo-200/30 top-[20px] right-[-40px]"></div>
    <div class="hero-orb orb-3 w-40 h-40 bg-sky-200/30 bottom-[0px] left-[30%]"></div>

    <div class="container mx-auto px-5 text-center max-w-3xl relative z-10">
        {{-- Badge --}}
        <span class="badge-pulse inline-flex items-center gap-1.5 py-1.5 px-4 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 mb-6 border border-blue-200/60 shadow-sm">
            ✨ Phương pháp học từ vựng thế hệ mới
        </span>

        {{-- Heading --}}
        <h1 class="reveal text-4xl sm:text-6xl font-extrabold text-gray-900 tracking-tight leading-none">
            Learn Vocabulary
            <span class="animated-gradient-text"> Smarter</span>,
            Not Harder
        </h1>

        <p class="reveal stagger-2 mt-6 text-lg sm:text-xl text-gray-500 leading-relaxed max-w-2xl mx-auto">
            Xóa tan nỗi lo "học trước quên sau". Làm chủ <strong class="text-gray-800">3000+</strong> từ vựng tiếng Anh thông dụng qua các chủ đề sinh động và lộ trình khoa học.
        </p>

        {{-- CTA Buttons --}}
        <div class="reveal stagger-3 mt-10 flex flex-wrap justify-center gap-4">
            <a href="#categories"
               class="ripple-btn px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 transition-all duration-200 hover:-translate-y-0.5 active:scale-95">
                🚀 Học thử ngay miễn phí
            </a>
            <a href="#features"
               class="px-8 py-4 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200 hover:-translate-y-0.5 shadow-sm">
                Tìm hiểu phương pháp
            </a>
        </div>

        {{-- Stats --}}
        <div class="reveal stagger-4 mt-12 pt-8 border-t border-gray-200/70 grid grid-cols-3 gap-4 max-w-sm mx-auto sm:max-w-none sm:flex sm:justify-center sm:gap-16 text-gray-500 text-sm">
            <div class="text-center">
                <b class="text-gray-900 text-2xl font-extrabold block count-up" data-target="10000" data-suffix="+">10,000+</b>
                <span class="text-xs text-gray-400 mt-0.5 block">Người đang học</span>
            </div>
            <div class="text-center">
                <b class="text-gray-900 text-2xl font-extrabold block count-up" data-target="95" data-suffix="%">95%</b>
                <span class="text-xs text-gray-400 mt-0.5 block">Nhớ từ lâu hơn</span>
            </div>
            <div class="text-center">
                <b class="text-gray-900 text-2xl font-extrabold block count-up" data-target="100" data-suffix="+">100+</b>
                <span class="text-xs text-gray-400 mt-0.5 block">Chủ đề thực tế</span>
            </div>
        </div>
    </div>
</section>

<!-- 2. CATEGORIES SECTION -->
<section id="categories" class="categories-section container mx-auto px-5 py-16 scroll-mt-6">
    <div class="reveal flex flex-col md:flex-row md:items-end justify-between mb-10">
        <div>
            <h2 class="text-3xl font-bold text-gray-950">Chọn danh mục bạn muốn chinh phục</h2>
            <p class="text-gray-500 mt-2">Các bài học được phân loại theo chuẩn quốc tế và ngữ cảnh thực tế</p>
        </div>
        <div class="mt-4 md:mt-0 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">
            <a href="{{ route('categories.index') }}">Xem tất cả danh mục &rarr;</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" data-stagger>
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category) }}"
               class="category-card card-glow reveal stagger-{{ min($loop->iteration, 8) }} group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm flex flex-col justify-between min-h-[150px]">
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
                    <div class="category-icon-box text-3xl bg-blue-50 p-3 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        📚
                    </div>
                </div>
                <div class="mt-6 text-sm font-semibold text-blue-600 flex items-center gap-1 opacity-0 translate-x-[-10px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                    Vào học ngay <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
                </div>
            </a>
        @endforeach
    </div>
</section>

<!-- 3. POPULAR TOPICS SECTION -->
@if(isset($popularTopics) && $popularTopics->isNotEmpty())
<section class="popular-topics-section bg-gradient-to-b from-white to-gray-50/50 py-16 border-t border-gray-100">
    <div class="container mx-auto px-5">
        <div class="reveal flex flex-col md:flex-row md:items-end justify-between mb-10">
            <div>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 uppercase tracking-wider mb-2">🔥 Được quan tâm nhất</span>
                <h2 class="text-3xl font-bold text-gray-900">Chủ đề nổi bật hôm nay</h2>
                <p class="text-gray-500 mt-1">Các bộ từ vựng được học nhiều nhất tuần qua</p>
            </div>
            <a href="{{ route('categories.index') }}" class="mt-4 md:mt-0 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                Khám phá thêm &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-stagger>
            @foreach ($popularTopics as $topic)
                <div class="topic-card card-glow reveal stagger-{{ min($loop->iteration, 8) }} group bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                {{ $topic->category->name ?? 'Category' }}
                            </span>
                            <span class="text-xs text-gray-400 font-medium">
                                📝 {{ $topic->vocabularies_count }} từ vựng
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                            {{ $topic->name }}
                        </h3>
                        <p class="text-gray-500 text-sm mt-2 line-clamp-2">
                            {{ $topic->description ?? 'Nắm vững các từ vựng và cấu trúc thiết yếu cho chủ đề này.' }}
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400">Bắt đầu học</span>
                        @if($topic->category)
                            <a href="{{ route('topics.index', ['category' => $topic->category->slug, 'topic' => $topic->slug]) }}"
                               class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 group-hover:gap-2 transition-all">
                                Học ngay <span class="topic-arrow inline-block">&rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- 4. FEATURES SECTION -->
<section id="features" class="features-section bg-gray-50 py-20 border-y border-gray-100">
    <div class="container mx-auto px-5">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <span class="inline-block text-xs font-semibold uppercase tracking-widest text-indigo-500 mb-3">Phương pháp</span>
            <h2 class="text-3xl font-bold text-gray-900">Tại sao bạn sẽ yêu thích Vocafy?</h2>
            <p class="text-gray-500 mt-3">Thiết kế khoa học, tối ưu hóa cho cách não bộ tiếp thu ngôn ngữ</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="reveal stagger-1 card-glow bg-white p-8 rounded-2xl border border-gray-100 shadow-sm group">
                <div class="text-2xl bg-amber-50 text-amber-600 w-14 h-14 rounded-2xl flex items-center justify-center font-bold mb-5 group-hover:scale-110 transition-transform">🧠</div>
                <h4 class="text-lg font-bold text-gray-900 mb-2">Lặp lại ngắt quãng</h4>
                <p class="text-gray-500 text-sm leading-relaxed">Thuật toán tự động nhắc nhở bạn ôn tập từ vựng ngay trước khi não chuẩn bị quên.</p>
            </div>

            <!-- Feature 2 -->
            <div class="reveal stagger-2 card-glow bg-white p-8 rounded-2xl border border-gray-100 shadow-sm group">
                <div class="text-2xl bg-green-50 text-green-600 w-14 h-14 rounded-2xl flex items-center justify-center font-bold mb-5 group-hover:scale-110 transition-transform">🎯</div>
                <h4 class="text-lg font-bold text-gray-900 mb-2">Học qua ngữ cảnh thực tế</h4>
                <p class="text-gray-500 text-sm leading-relaxed">Mỗi từ đi kèm ví dụ sinh động, phát âm chuẩn bản xứ và giải thích chi tiết.</p>
            </div>

            <!-- Feature 3 -->
            <div class="reveal stagger-3 card-glow bg-white p-8 rounded-2xl border border-gray-100 shadow-sm group">
                <div class="text-2xl bg-purple-50 text-purple-600 w-14 h-14 rounded-2xl flex items-center justify-center font-bold mb-5 group-hover:scale-110 transition-transform">📊</div>
                <h4 class="text-lg font-bold text-gray-900 mb-2">Theo dõi tiến độ trực quan</h4>
                <p class="text-gray-500 text-sm leading-relaxed">Thống kê chi tiết từng ngày, giúp bạn thấy rõ tiến bộ và duy trì động lực.</p>
            </div>
        </div>
    </div>
</section>

@endsection
