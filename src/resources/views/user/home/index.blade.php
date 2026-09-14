@extends('layouts.app')

@section('content')

<!-- 1. HERO SECTION -->
<section class="hero-section bg-gradient-to-b from-blue-50/50 to-white pt-20 pb-12">
    <div class="container mx-auto px-5 text-center max-w-3xl">
        <!-- Badge thu hút -->
        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 mb-6">
            ✨ Phương pháp học từ vựng thế hệ mới
        </span>
        
        <h1 class="text-4xl sm:text-6xl font-extrabold text-gray-900 tracking-tight leading-none">
            Learn Vocabulary <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Smarter</span>, Not Harder
        </h1>

        <p class="mt-6 text-lg sm:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
            Xóa tan nỗi lo "học trước quên sau". Làm chủ 3000+ từ vựng tiếng Anh thông dụng qua các chủ đề sinh động và lộ trình khoa học.
        </p>

        <!-- Nút Kêu gọi hành động (CTA) -->
        <div class="mt-10 flex flex-wrap justify-center gap-4">
            <a href="#categories" class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                Học thử ngay miễn phí
            </a>
            <a href="#features" class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors">
                Tìm hiểu phương pháp
            </a>
        </div>

        <!-- Số liệu uy tín nhỏ -->
        <div class="mt-12 pt-8 border-t border-gray-200 flex justify-center gap-8 sm:gap-16 text-gray-500 text-sm">
            <div><b class="text-gray-900 text-lg block">10,000+</b> Người đang học</div>
            <div><b class="text-gray-900 text-lg block">95%</b> Nhớ từ lâu hơn</div>
            <div><b class="text-gray-900 text-lg block">100+</b> Chủ đề thực tế</div>
        </div>
    </div>
</section>

<!-- 2. MAIN CONTENT: CATEGORIES -->
<section id="categories" class="categories-section container mx-auto px-5 py-16 scroll-mt-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10">
        <div>
            <h2 class="text-3xl font-bold text-gray-950">Chọn danh mục bạn muốn chinh phục</h2>
            <p class="text-gray-500 mt-2">Các bài học được phân loại theo chuẩn quốc tế và ngữ cảnh thực tế</p>
        </div>
        <div class="mt-4 md:mt-0 text-sm font-medium text-blue-600 hover:underline">
            <a href="{{ route('categories.index') }}">Xem tất cả danh mục &rarr;</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category) }}"
                class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-blue-500 hover:shadow-xl flex flex-col justify-between min-h-[150px]">

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
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10">
            <div>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 uppercase tracking-wider mb-2">🔥 Được quan tâm nhất</span>
                <h2 class="text-3xl font-bold text-gray-900">Chủ đề nổi bật hôm nay</h2>
                <p class="text-gray-500 mt-1">Các bộ từ vựng được học nhiều nhất tuần qua</p>
            </div>
            <a href="{{ route('categories.index') }}" class="mt-4 md:mt-0 text-sm font-semibold text-blue-600 hover:text-blue-700">
                Khám phá thêm &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($popularTopics as $topic)
                <div class="group bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-xl hover:border-blue-500 transition-all duration-300 flex flex-col justify-between">
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
                               class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 group-hover:text-blue-700">
                                Học ngay &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- 4. FEATURE SECTION (TÍNH NĂNG NỔI BẬT) -->
<section id="features" class="features-section bg-gray-50 py-16 border-y border-gray-100">
    <div class="container mx-auto px-5">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Tại sao bạn sẽ yêu thích Vocafy?</h2>
            <p class="text-gray-500 mt-2">Phương pháp học thông minh tối ưu hóa cho bộ não của bạn</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="text-2xl bg-amber-50 text-amber-600 w-12 h-12 rounded-xl flex items-center justify-center font-bold mb-5">🧠</div>
                <h4 class="text-lg font-bold text-gray-900 mb-2">Lặp lại ngắt quãng (Spaced Repetition)</h4>
                <p class="text-gray-500 text-sm leading-relaxed">Thuật toán tự động nhắc nhở bạn ôn tập lại từ vựng ngay trước khi bạn chuẩn bị quên nó.</p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="text-2xl bg-green-50 text-green-600 w-12 h-12 rounded-xl flex items-center justify-center font-bold mb-5">🎯</div>
                <h4 class="text-lg font-bold text-gray-900 mb-2">Học qua ngữ cảnh thực tế</h4>
                <p class="text-gray-500 text-sm leading-relaxed">Không học từ chết. Mỗi từ vựng đều đi kèm ví dụ, phát âm chuẩn bản xứ và giải thích chi tiết.</p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="text-2xl bg-purple-50 text-purple-600 w-12 h-12 rounded-xl flex items-center justify-center font-bold mb-5">📊</div>
                <h4 class="text-lg font-bold text-gray-900 mb-2">Theo dõi tiến độ trực quan</h4>
                <p class="text-gray-500 text-sm leading-relaxed">Hệ thống thống kê chi tiết giúp bạn thấy rõ lượng từ vựng mình đã ghi nhớ mỗi ngày.</p>
            </div>
        </div>
    </div>
</section>

@endsection