<footer class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white mt-20">
    {{-- Decorative gradient line on top --}}
    <div class="h-px w-full bg-gradient-to-r from-transparent via-indigo-500 to-transparent opacity-60"></div>

    {{-- Subtle orb background --}}
    <div class="hero-orb orb-2 w-80 h-80 bg-indigo-600/10 bottom-[-60px] right-[-60px] pointer-events-none"></div>
    <div class="hero-orb orb-3 w-52 h-52 bg-blue-500/10 top-[-20px] left-[-30px] pointer-events-none"></div>

    <div class="container mx-auto px-5 py-14 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">

            {{-- Brand --}}
            <div class="reveal-left">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-extrabold text-lg shadow-lg">V</div>
                    <span class="text-xl font-extrabold tracking-tight">Vocafy</span>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                    Nền tảng học từ vựng tiếng Anh thông minh. Làm chủ TOEIC, IELTS, TOEFL qua phương pháp lặp lại ngắt quãng khoa học.
                </p>
            </div>

            {{-- Quick Links --}}
            <div class="reveal">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Khám phá</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('home') }}" class="text-slate-400 hover:text-white text-sm transition-colors hover:translate-x-1 inline-flex items-center gap-1.5 group"><span class="group-hover:text-indigo-400 transition-colors">→</span> Trang chủ</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-slate-400 hover:text-white text-sm transition-colors hover:translate-x-1 inline-flex items-center gap-1.5 group"><span class="group-hover:text-indigo-400 transition-colors">→</span> Danh mục học</a></li>
                    <li><a href="{{ route('writingAi') }}" class="text-slate-400 hover:text-white text-sm transition-colors hover:translate-x-1 inline-flex items-center gap-1.5 group"><span class="group-hover:text-indigo-400 transition-colors">→</span> Writing AI</a></li>
                    @guest
                    <li><a href="{{ route('register') }}" class="text-slate-400 hover:text-white text-sm transition-colors hover:translate-x-1 inline-flex items-center gap-1.5 group"><span class="group-hover:text-indigo-400 transition-colors">→</span> Đăng ký miễn phí</a></li>
                    @endguest
                </ul>
            </div>

            {{-- Tagline / Stats --}}
            <div class="reveal-right">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Con số ấn tượng</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/5 rounded-xl p-3 text-center border border-white/10">
                        <div class="text-2xl font-extrabold text-white count-up" data-target="3000" data-suffix="+">3000+</div>
                        <div class="text-xs text-slate-400 mt-0.5">Từ vựng</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 text-center border border-white/10">
                        <div class="text-2xl font-extrabold text-white count-up" data-target="100" data-suffix="+">100+</div>
                        <div class="text-xs text-slate-400 mt-0.5">Chủ đề</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Bottom bar --}}
        <div class="pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-slate-500 text-sm">© {{ date('Y') }} Vocafy. All rights reserved.</p>
            <p class="text-slate-600 text-xs">Built with ❤️ for learners</p>
        </div>
    </div>
</footer>