@extends('layouts.app')

@section('content')

<x-page-header 
    title="Bảng Điều Khiển Học Tập"
    badge="Trang cá nhân"
    description="Chào mừng trở lại, {{ Auth::user()->name }}! Theo dõi tiến độ học và tiếp tục chinh phục các chủ đề từ vựng mới."
    :breadcrumbs="['Dashboard' => '']" 
/>

@php
    use App\Models\UserVocabulary;
    use App\Models\Vocabulary;
    use App\Models\Topic;
    use App\Models\Category;

    $userId = Auth::id();

    $totalVocabs    = Vocabulary::count();
    $totalTopics    = Topic::count();
    $totalCategories = Category::count();

    $userStats = UserVocabulary::where('user_id', $userId)
        ->selectRaw("
            COUNT(*) as total_tracked,
            SUM(CASE WHEN status = 'learning' THEN 1 ELSE 0 END) as learning_count,
            SUM(CASE WHEN status = 'mastered' THEN 1 ELSE 0 END) as mastered_count
        ")
        ->first();

    $trackedCount  = (int) ($userStats->total_tracked ?? 0);
    $learningCount = (int) ($userStats->learning_count ?? 0);
    $masteredCount = (int) ($userStats->mastered_count ?? 0);
    $overallPercent = $totalVocabs > 0 ? round($masteredCount / $totalVocabs * 100) : 0;

    // 5 chủ đề user vừa ôn gần nhất
    // Lấy topic_id gần đây nhất dựa trên last_review_at của user
    $recentTopicIds = UserVocabulary::where('user_vocabularies.user_id', $userId)
        ->whereNotNull('user_vocabularies.last_review_at')
        ->join('vocabularies', 'user_vocabularies.vocabulary_id', '=', 'vocabularies.id')
        ->select('vocabularies.topic_id', \DB::raw('MAX(user_vocabularies.last_review_at) as max_reviewed'))
        ->groupBy('vocabularies.topic_id')
        ->orderByDesc('max_reviewed')
        ->take(5)
        ->pluck('vocabularies.topic_id');

    $recentTopics = Topic::withCount('vocabularies')
        ->with('category')
        ->whereIn('id', $recentTopicIds)
        ->get()
        ->sortBy(fn($t) => array_search($t->id, $recentTopicIds->toArray()))
        ->values();

@endphp

<div class="container mx-auto px-5 py-10">

    {{-- 1. Stats Quick Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold">
                📚
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Danh mục</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $totalCategories }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                📁
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Chủ đề</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $totalTopics }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold">
                📝
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Đã học</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ $trackedCount }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">/ {{ $totalVocabs }} từ</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold">
                ✅
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Đã thuộc</p>
                <h3 class="text-2xl font-bold text-emerald-600">{{ $masteredCount }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $overallPercent }}% tổng từ vựng</p>
            </div>
        </div>
    </div>

    {{-- 2. Tiến độ tổng thể --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-10">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h3 class="text-base font-bold text-gray-900">Tiến độ tổng thể</h3>
                <p class="text-sm text-gray-500 mt-0.5">Theo dõi quá trình chinh phục toàn bộ kho từ vựng</p>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-1.5 text-gray-500">
                    <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>
                    Đang học: <strong class="text-gray-700 ml-1">{{ $learningCount }}</strong>
                </span>
                <span class="flex items-center gap-1.5 text-gray-500">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                    Đã thuộc: <strong class="text-gray-700 ml-1">{{ $masteredCount }}</strong>
                </span>
            </div>
        </div>

        <div class="relative w-full h-4 bg-gray-100 rounded-full overflow-hidden">
            @php
                $learnedPct = $totalVocabs > 0 ? round(($learningCount + $masteredCount) / $totalVocabs * 100) : 0;
            @endphp
            <div class="absolute left-0 top-0 h-4 bg-amber-300 rounded-full transition-all" style="width: {{ $learnedPct }}%"></div>
            <div class="absolute left-0 top-0 h-4 bg-emerald-500 rounded-full transition-all" style="width: {{ $overallPercent }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-400 mt-2">
            <span>0 từ</span>
            <span class="font-semibold text-emerald-600">{{ $overallPercent }}% hoàn thành</span>
            <span>{{ $totalVocabs }} từ</span>
        </div>
    </div>

    {{-- 3. Admin Quick Access Banner --}}
    @if(Auth::user()->hasAnyRole(['super-admin', 'admin', 'editor', 'moderator']))
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 sm:p-8 text-white mb-10 shadow-lg shadow-blue-500/20 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-semibold bg-white/20 text-white mb-3">
                    👑 Quyền quản trị viên
                </span>
                <h3 class="text-2xl font-bold">Trang Quản Trị Hệ Thống (Filament Admin)</h3>
                <p class="text-blue-100 text-sm mt-1 max-w-xl">
                    Bạn có quyền truy cập vào trang Admin để quản lý danh mục, bài học, danh sách câu hỏi và người dùng.
                </p>
            </div>
            <a href="/admin" class="px-6 py-3 bg-white hover:bg-blue-50 text-blue-700 font-bold rounded-xl shadow transition-all whitespace-nowrap active:scale-95">
                Vào Admin Panel &rarr;
            </a>
        </div>
    @endif

    {{-- 4. Chủ đề vừa học gần đây --}}
    @if($recentTopics->isNotEmpty())
    <div class="mb-10">
        <h3 class="text-base font-bold text-gray-900 mb-4">🕐 Tiếp tục học gần đây</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($recentTopics as $recentTopic)
            @php
                $topicVocabIds = $recentTopic->vocabularies->pluck('id');
                $topicProgress = UserVocabulary::where('user_id', $userId)
                    ->whereIn('vocabulary_id', $topicVocabIds)
                    ->selectRaw("SUM(CASE WHEN status='mastered' THEN 1 ELSE 0 END) as mastered, COUNT(*) as total")
                    ->first();
                $tMastered = (int)($topicProgress->mastered ?? 0);
                $tTotal    = $recentTopic->vocabularies_count;
                $tPct      = $tTotal > 0 ? round($tMastered / $tTotal * 100) : 0;
            @endphp
            <a href="{{ route('topics.index', [$recentTopic->category->slug ?? '_', $recentTopic->slug]) }}"
               class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md hover:border-blue-300 transition-all group">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition-colors truncate pr-2">{{ $recentTopic->name }}</h4>
                    <span class="shrink-0 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">{{ $tPct }}%</span>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-2 bg-emerald-500 rounded-full transition-all" style="width: {{ $tPct }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-2">{{ $tMastered }} / {{ $tTotal }} từ đã thuộc</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 5. Learning Action Hub --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Card 1 --}}
        <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4">
                    🎯
                </div>
                <h3 class="text-xl font-bold text-gray-900">Bắt đầu học từ vựng</h3>
                <p class="text-gray-500 text-sm mt-2 leading-relaxed">
                    Khám phá kho từ vựng tiếng Anh theo chứng chỉ TOEIC, IELTS, TOEFL hoặc các chủ đề giao tiếp thường ngày.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('categories.index') }}" class="inline-flex items-center gap-2 font-semibold text-blue-600 hover:text-blue-700">
                    Khám phá ngay &rarr;
                </a>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center text-2xl mb-4">
                    👤
                </div>
                <h3 class="text-xl font-bold text-gray-900">Cài đặt hồ sơ cá nhân</h3>
                <p class="text-gray-500 text-sm mt-2 leading-relaxed">
                    Cập nhật thông tin tài khoản, đổi mật khẩu và quản lý thiết lập bảo mật của bạn.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 font-semibold text-gray-700 hover:text-blue-600">
                    Chỉnh sửa hồ sơ &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
