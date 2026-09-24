<x-guest-layout>
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 mb-3 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">Quên mật khẩu?</h2>
        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
            Đừng lo lắng! Nhập địa chỉ email đăng ký của bạn, Vocafy sẽ gửi liên kết bảo mật để bạn thiết lập mật khẩu mới ngay lập tức.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Địa chỉ Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="nhap-email@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-5">
            <x-primary-button class="w-full justify-center py-2.5 text-sm font-semibold tracking-wide">
                {{ __('Gửi liên kết đặt lại mật khẩu') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-100 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Quay lại trang đăng nhập
        </a>
    </div>

    <!-- Market Support Box -->
    <div class="mt-6 p-3.5 bg-gray-50 rounded-lg border border-gray-200/80 text-center">
        <p class="text-xs text-gray-500">
            Cần trợ giúp thêm? Liên hệ Trung tâm Hỗ trợ khách hàng:
        </p>
        <a href="mailto:support.vocafy@emptydev.io.vn" class="mt-1 inline-block text-xs font-semibold text-indigo-600 hover:underline">
            support.vocafy@emptydev.io.vn
        </a>
    </div>
</x-guest-layout>
