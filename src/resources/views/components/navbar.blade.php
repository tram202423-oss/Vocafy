<nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all">
    <div class="container mx-auto px-5 py-3.5 flex items-center justify-between">

        <!-- Logo -->
        <a href="/" class="flex items-center gap-2 font-bold text-blue-600 transition-transform active:scale-95">
            <img src="{{ asset('images/logo.png') }}" alt="Vocafy" class="h-9 w-auto">
        </a>

        <!-- Menu Links -->
        <div>
            <ul class="flex items-center gap-6 text-sm font-medium text-gray-600">
                <li>
                    <a href="/" class="hover:text-blue-600 transition-colors {{ request()->is('/') ? 'text-blue-600 font-semibold' : '' }}">Home</a>
                </li>

                {{-- Categories Dropdown --}}
                <!-- <li x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        @click.outside="open = false"
                        class="flex items-center gap-1 hover:text-blue-600 transition-colors py-1 focus:outline-none"
                        :class="{ 'text-blue-600': open }"
                    >
                        Categories
                        <svg class="w-4 h-4 transition-transform duration-200"
                             :class="{ 'rotate-180 text-blue-600': open }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <ul
                        x-show="open"
                        x-cloak 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                        class="absolute left-0 top-full mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 overflow-hidden"
                    >
                        @foreach($categories as $category)
                            <li>
                                <a href="/categories/{{ $category->slug ?? $category->id }}"
                                   class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li> -->
                <li>
                    <a href="/categories" class="hover:text-blue-600 transition-colors {{ request()->is('categories') ? 'text-blue-600 font-semibold' : '' }}">Categories</a>
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
                        <a href="/dashboard" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                            Dashboard
                        </a>
                    </li>
                @endauth
            </ul>
        </div>

    </div>
</nav>