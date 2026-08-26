<nav x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all">
    <div class="container mx-auto px-5 py-3.5 flex items-center justify-between">

        <a href="/" class="flex items-center gap-2 font-bold text-blue-600 transition-transform active:scale-95">
            <img src="{{ asset('images/logo.png') }}" alt="Vocafy" class="h-9 w-auto">
        </a>

        <div class="hidden md:flex items-center">
            <ul class="flex items-center gap-6 text-sm font-medium text-gray-600">
                <li>
                    <a href="/" class="hover:text-blue-600 transition-colors {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">Home</a>
                </li>
                <li x-data="{ catDropdown: false }" class="relative" @mouseleave="catDropdown = false">
                    <div class="flex items-center gap-1">
                        <a href="{{ route('categories.index') }}" class="hover:text-blue-600 transition-colors {{ request()->is('categories*') ? 'text-blue-600 font-semibold' : '' }}">
                            Categories
                        </a>
                        @if(isset($categories) && $categories->isNotEmpty())
                            <button @click="catDropdown = !catDropdown" @mouseenter="catDropdown = true" class="text-gray-400 hover:text-blue-600 focus:outline-none p-0.5">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="catDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    @if(isset($categories) && $categories->isNotEmpty())
                        <div x-show="catDropdown" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                             class="absolute left-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-50">
                            @foreach($categories as $cat)
                                <a href="{{ route('categories.show', $cat) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    {{ $cat->name }}
                                </a>
                            @endforeach
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('categories.index') }}" class="block px-4 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                Xem tất cả &rarr;
                            </a>
                        </div>
                    @endif
                </li>

                @guest
                    <li>
                        <a href="/login" class="hover:text-blue-600 transition-colors">Login</a>
                    </li>
                    <li>
                        <a href="/register" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-sm shadow-blue-500/10 active:scale-98">
                            Register
                        </a>
                    </li>
                @endguest

                @auth
                    <li>
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors {{ request()->routeIs('dashboard') ? 'text-blue-600 font-semibold' : '' }}">Dashboard</a>
                    </li>
                    <div class="flex items-center">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->name }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @if(Auth::user()->hasAnyRole(['super-admin', 'admin', 'editor', 'moderator']))
                                    <x-dropdown-link href="/admin" class="font-semibold text-blue-600 bg-blue-50/50 hover:bg-blue-100">
                                        ⚡ {{ __('Trang Quản Trị Admin') }}
                                    </x-dropdown-link>
                                    <div class="border-t border-gray-100 my-1"></div>
                                @endif

                                <x-dropdown-link :href="route('dashboard')">
                                    {{ __('Dashboard') }}
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endauth
            </ul>
        </div>

        <div class="flex items-center md:hidden">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-600 hover:text-blue-600 focus:outline-none p-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="mobileMenuOpen" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="md:hidden border-t border-gray-100 bg-white">
        <div class="px-5 pt-3 pb-6 space-y-3">
            <a href="/" class="block py-2 text-base font-medium text-gray-700 hover:text-blue-600 transition-colors {{ request()->is('/') ? 'text-blue-600 font-semibold bg-blue-50/50 px-3 rounded-xl' : '' }}">Home</a>
            <a href="/categories" class="block py-2 text-base font-medium text-gray-700 hover:text-blue-600 transition-colors {{ request()->is('categories*') ? 'text-blue-600 font-semibold bg-blue-50/50 px-3 rounded-xl' : '' }}">Categories</a>
            
            <div class="border-t border-gray-100 pt-3">
                @guest
                    <div class="grid grid-cols-2 gap-3 mt-1">
                        <a href="/login" class="flex items-center justify-center py-2.5 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                            Login
                        </a>
                        <a href="/register" class="flex items-center justify-center py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-sm shadow-blue-500/10">
                            Register
                        </a>
                    </div>
                @endguest

                @auth
                    <div class="px-3 py-2 bg-gray-50 rounded-xl mb-3">
                        <p class="text-xs text-gray-400 font-medium">Đang đăng nhập</p>
                        <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                    </div>
                    
                    <div class="space-y-1">
                        @if(Auth::user()->hasAnyRole(['super-admin', 'admin', 'editor', 'moderator']))
                            <a href="/admin" class="block py-2 px-3 text-sm font-semibold text-blue-600 bg-blue-50/50 hover:bg-blue-100 rounded-lg">
                                ⚡ Trang Quản Trị Admin
                            </a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="block py-2 px-3 text-sm font-medium text-gray-600 hover:text-blue-600 rounded-lg hover:bg-gray-50">
                            📊 Dashboard
                        </a>
                        <a href="{{ route('profile.edit') }}" class="block py-2 px-3 text-sm font-medium text-gray-600 hover:text-blue-600 rounded-lg hover:bg-gray-50">
                            👤 Profile Setting
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block py-2 px-3 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                🚪 Log Out
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>