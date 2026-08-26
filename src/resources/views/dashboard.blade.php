@extends('layouts.app')

@section('content')

<x-page-header 
    title="Bảng Điều Khiển Học Tập"
    badge="Trang cá nhân"
    description="Chào mừng trở lại, {{ Auth::user()->name }}! Theo dõi tiến độ học và tiếp tục chinh phục các chủ đề từ vựng mới."
    :breadcrumbs="['Dashboard' => '']" 
/>

<div class="container mx-auto px-5 py-10">
    <!-- 1. Stats Quick Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold">
                📚
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Danh mục</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ \App\Models\Category::count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                📁
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Chủ đề</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ \App\Models\Topic::count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold">
                📝
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tổng từ vựng</p>
                <h3 class="text-2xl font-bold text-gray-900">{{ \App\Models\Vocabulary::count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-2xl font-bold">
                ✨
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Trạng thái</p>
                <h3 class="text-sm font-bold text-green-600">Đang hoạt động</h3>
            </div>
        </div>
    </div>

    <!-- 2. Admin Quick Access Banner (if admin) -->
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

    <!-- 3. Learning Action Hub -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
        <!-- Card 1 -->
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

        <!-- Card 2 -->
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
