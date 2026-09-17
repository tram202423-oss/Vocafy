<x-guest-layout :raw="true">
    <div class="min-h-screen bg-gradient-to-b from-[#ebf3fc] via-[#f6faff] to-[#ebf3fc] flex flex-col justify-center items-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-[420px] mx-auto">

            <!-- Logo & Brand Header -->
            <div class="flex flex-col items-center text-center mb-6">
                <a href="/" class="inline-block transition-transform hover:scale-105 mb-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Vocafy" class="h-16 sm:h-20 w-auto object-contain drop-shadow-sm">
                </a>

                <!-- Heading -->
                <h2 class="text-[22px] font-bold text-gray-900 mt-1 tracking-tight">
                    Tạo tài khoản mới
                </h2>
                <p class="text-[13px] text-gray-500 mt-1 font-normal">
                    Bắt đầu hành trình chinh phục tiếng Anh cùng Vocafy
                </p>
            </div>

            <!-- Registration Card Container -->
            <div class="bg-white rounded-[2rem] p-6 sm:p-7 shadow-[0_12px_45px_-12px_rgba(37,99,235,0.09)] border border-gray-100">

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- Họ và tên -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Họ và tên
                        </label>
                        <div class="relative flex items-center bg-[#f8fafc] border border-gray-200/90 rounded-2xl px-3.5 py-3 focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                            <svg class="w-5 h-5 text-gray-400 me-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Nguyễn Văn A"
                                class="w-full bg-transparent border-0 p-0 text-sm font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                    </div>

                    <!-- Email -->
                    <div class="mt-4">
                        <label for="email" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Email
                        </label>
                        <div class="relative flex items-center bg-[#f8fafc] border border-gray-200/90 rounded-2xl px-3.5 py-3 focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                            <svg class="w-5 h-5 text-gray-400 me-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                                placeholder="tram202423@gmail.com"
                                class="w-full bg-transparent border-0 p-0 text-sm font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                    </div>

                    <!-- Mật khẩu -->
                    <div class="mt-4">
                        <label for="reg_password" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Mật khẩu
                        </label>
                        <div class="relative flex items-center bg-[#f8fafc] border border-gray-200/90 rounded-2xl px-3.5 py-3 focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                            <svg class="w-5 h-5 text-gray-400 me-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input
                                id="reg_password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                placeholder="Tối thiểu 8 ký tự"
                                oninput="checkPasswordStrength(this.value)"
                                class="w-full bg-transparent border-0 p-0 text-sm font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            />
                            <button
                                type="button"
                                onclick="toggleRegPasswordVisibility()"
                                class="text-gray-400 hover:text-gray-600 ms-2 focus:outline-none shrink-0"
                                aria-label="Toggle password visibility"
                            >
                                <svg id="reg-eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />

                        <!-- Password Strength Bars -->
                        <div class="mt-2.5 flex items-center gap-2">
                            <div class="flex-1 grid grid-cols-4 gap-1.5">
                                <span id="bar-1" class="h-1 rounded-full bg-gray-200 transition-colors duration-300"></span>
                                <span id="bar-2" class="h-1 rounded-full bg-gray-200 transition-colors duration-300"></span>
                                <span id="bar-3" class="h-1 rounded-full bg-gray-200 transition-colors duration-300"></span>
                                <span id="bar-4" class="h-1 rounded-full bg-gray-200 transition-colors duration-300"></span>
                            </div>
                            <span id="strength-text" class="text-[11px] font-semibold text-gray-400 shrink-0 select-none">
                                Độ bảo mật
                            </span>
                        </div>
                    </div>

                    <!-- Xác nhận mật khẩu -->
                    <div class="mt-4">
                        <label for="password_confirmation" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Xác nhận mật khẩu
                        </label>
                        <div class="relative flex items-center bg-[#f8fafc] border border-gray-200/90 rounded-2xl px-3.5 py-3 focus-within:border-blue-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                            <svg class="w-5 h-5 text-gray-400 me-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Nhập lại mật khẩu"
                                class="w-full bg-transparent border-0 p-0 text-sm font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                    </div>

                    <!-- Checkbox Điều khoản -->
                    <div class="mt-4">
                        <label for="terms" class="inline-flex items-start cursor-pointer select-none">
                            <input
                                id="terms"
                                type="checkbox"
                                name="terms"
                                checked
                                required
                                class="w-4 h-4 mt-0.5 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 focus:ring-offset-0 transition-colors"
                            />
                            <span class="ms-2 text-xs text-gray-600 leading-snug">
                                Tôi đồng ý với <a href="#" class="text-blue-600 font-medium hover:underline">Điều khoản</a> & <a href="#" class="text-blue-600 font-medium hover:underline">Chính sách bảo mật</a> của Vocafy.
                            </span>
                        </label>
                    </div>

                    <!-- Nút ĐĂNG KÝ NGAY -->
                    <div class="mt-5">
                        <button
                            type="submit"
                            class="w-full py-3.5 px-4 bg-[#2563eb] hover:bg-[#1d4ed8] active:scale-[0.99] text-white text-[13px] font-bold tracking-wider uppercase rounded-2xl shadow-lg shadow-blue-600/30 transition-all duration-200 flex items-center justify-center gap-2"
                        >
                            <span>ĐĂNG KÝ NGAY</span>
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Dải phân cách -->
                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200/80"></div>
                    </div>
                    <div class="relative flex justify-center text-[10.5px] uppercase tracking-wider">
                        <span class="px-3 bg-white text-gray-400 font-bold">HOẶC TIẾP TỤC VỚI</span>
                    </div>
                </div>

                <!-- Social Login Buttons -->
                <div class="space-y-3">
                    <!-- Google SSO -->
                    <a
                        href="{{ route('auth.google') }}"
                        class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-200/90 rounded-2xl bg-white hover:bg-gray-50/80 hover:border-gray-300 shadow-sm text-sm font-semibold text-gray-700 transition-all duration-200 active:scale-[0.99]"
                    >
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
                        </svg>
                        <span>Đăng ký bằng Google</span>
                    </a>

                    <!-- Apple SSO -->
                    <button
                        type="button"
                        onclick="alert('Tính năng đăng ký với Apple sẽ sớm ra mắt!')"
                        class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-200/90 rounded-2xl bg-white hover:bg-gray-50/80 hover:border-gray-300 shadow-sm text-sm font-semibold text-gray-700 transition-all duration-200 active:scale-[0.99]"
                    >
                        <svg class="w-5 h-5 shrink-0 fill-current text-gray-900" viewBox="0 0 24 24">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 0.92-2.85-.9.04-1.99.6-2.63 1.35-.57.65-1.06 1.71-.93 2.73 1 .08 2.02-.48 2.64-1.23z" />
                        </svg>
                        <span>Tiếp tục với Apple</span>
                    </button>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="mt-6 text-center">
                <p class="text-[13px] text-gray-600 font-medium">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-700 underline ms-1">
                        Đăng nhập
                    </a>
                </p>
                <p class="text-[11px] text-gray-400 mt-2.5 leading-relaxed">
                    Bằng cách tiếp tục, bạn đồng ý với
                    <a href="#" class="underline hover:text-gray-600">Điều khoản</a> &
                    <a href="#" class="underline hover:text-gray-600">Chính sách</a> của Vocafy.
                </p>
            </div>

        </div>
    </div>

    <!-- Interactive Script for Show/Hide Password & Password Strength -->
    <script>
        function toggleRegPasswordVisibility() {
            const passwordInput = document.getElementById('reg_password');
            const eyeIcon = document.getElementById('reg-eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }

        function checkPasswordStrength(val) {
            let score = 0;
            if (!val) {
                resetBars();
                return;
            }

            if (val.length >= 6) score++;
            if (val.length >= 8) score++;
            if (/[0-9]/.test(val) && /[a-zA-Z]/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val) || val.length >= 12) score++;

            const bar1 = document.getElementById('bar-1');
            const bar2 = document.getElementById('bar-2');
            const bar3 = document.getElementById('bar-3');
            const bar4 = document.getElementById('bar-4');
            const text = document.getElementById('strength-text');

            // Reset all
            [bar1, bar2, bar3, bar4].forEach(b => {
                b.className = 'h-1 rounded-full bg-gray-200 transition-colors duration-300';
            });

            if (score === 1) {
                bar1.className = 'h-1 rounded-full bg-red-500 transition-colors duration-300';
                text.textContent = 'Yếu';
                text.className = 'text-[11px] font-semibold text-red-500 shrink-0 select-none';
            } else if (score === 2) {
                bar1.className = 'h-1 rounded-full bg-amber-500 transition-colors duration-300';
                bar2.className = 'h-1 rounded-full bg-amber-500 transition-colors duration-300';
                text.textContent = 'Trung bình';
                text.className = 'text-[11px] font-semibold text-amber-500 shrink-0 select-none';
            } else if (score === 3) {
                bar1.className = 'h-1 rounded-full bg-emerald-500 transition-colors duration-300';
                bar2.className = 'h-1 rounded-full bg-emerald-500 transition-colors duration-300';
                bar3.className = 'h-1 rounded-full bg-emerald-500 transition-colors duration-300';
                text.textContent = 'Khá mạnh';
                text.className = 'text-[11px] font-semibold text-emerald-600 shrink-0 select-none';
            } else if (score >= 4) {
                [bar1, bar2, bar3, bar4].forEach(b => {
                    b.className = 'h-1 rounded-full bg-emerald-600 transition-colors duration-300';
                });
                text.textContent = 'Rất mạnh';
                text.className = 'text-[11px] font-semibold text-emerald-600 shrink-0 select-none';
            }
        }

        function resetBars() {
            ['bar-1', 'bar-2', 'bar-3', 'bar-4'].forEach(id => {
                document.getElementById(id).className = 'h-1 rounded-full bg-gray-200 transition-colors duration-300';
            });
            const text = document.getElementById('strength-text');
            text.textContent = 'Độ bảo mật';
            text.className = 'text-[11px] font-semibold text-gray-400 shrink-0 select-none';
        }
    </script>
</x-guest-layout>
