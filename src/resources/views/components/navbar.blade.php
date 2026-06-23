<nav class="bg-white shadow">
    <div class="container mx-auto px-5 py-4 flex justify-between">

        <a href="/" class="text-2xl font-bold text-blue-600">
            <img src="{{ asset('images/logo.png') }}" alt="Vocafy" class="h-10 w-auto">
        </a>

        <div>
            <ul class="flex items-center gap-4">
                <li>
                    <a href="/" class="hover:text-blue-600">Home</a>
                </li>

                {{-- Categories Dropdown --}}
                <li x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        @click.outside="open = false"
                        class="flex items-center gap-1 hover:text-blue-600"
                    >
                        Categories
                        <svg class="w-4 h-4 transition-transform duration-200"
                             :class="{ 'rotate-180': open }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Dropdown --}}
                    <ul
                        x-show="open"
                        x-cloak 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute left-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-100 z-50"
                    >
                        @foreach($categories as $category)
                            <li>
                                <a href="/categories/{{ $category->slug ?? $category->id }}"
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                @guest
                    <li>
                        <a href="/login" class="hover:text-blue-600">Login</a>
                    </li>
                    <li>
                        <a href="/register" class="hover:text-blue-600">Register</a>
                    </li>
                @endguest

                @auth
                    <li>
                        <a href="/dashboard" class="hover:text-blue-600">Dashboard</a>
                    </li>
                @endauth
            </ul>
        </div>

    </div>
</nav>