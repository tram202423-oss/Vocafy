@props([
    'title',
    'description' => null,
    'badge' => null,
    'breadcrumbs' => [],
    'progress' => null
])

<section class="relative overflow-hidden bg-gradient-to-b from-blue-50/60 to-white pt-12 pb-8 border-b border-gray-100 w-full">
    {{-- Subtle orb --}}
    <div class="hero-orb orb-1 w-64 h-64 bg-blue-100/40 top-[-50px] right-[-30px] pointer-events-none"></div>

    <div class="container mx-auto px-5 relative z-10">
        {{-- Breadcrumbs --}}
        @if(!empty($breadcrumbs))
            <nav class="reveal-left flex items-center gap-2 text-sm text-gray-400 mb-4 flex-wrap">
                <a href="{{ route('categories.index') }}" class="hover:text-blue-600 transition-colors">Categories</a>
                @foreach($breadcrumbs as $label => $url)
                    <span class="text-gray-300">/</span>
                    @if(!$loop->last)
                        <a href="{{ $url }}" class="hover:text-blue-600 transition-colors">{{ $label }}</a>
                    @else
                        <span class="text-gray-700 font-medium">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                @if($badge)
                    <span class="badge-pulse inline-block py-1 px-3 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg mb-3 border border-blue-100">
                        {{ $badge }}
                    </span>
                @endif
                <h1 class="reveal text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    {{ $title }}
                </h1>
                @if($description)
                    <p class="reveal stagger-2 mt-2 text-gray-500 max-w-xl leading-relaxed">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if($progress !== null)
                <div class="reveal-right bg-white border border-gray-200 p-5 rounded-2xl shadow-sm min-w-[240px]">
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="text-gray-500 font-medium">Tiến độ bài học</span>
                        <span class="text-blue-600 font-bold">{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="progress-bar-animated bg-gradient-to-r from-blue-500 to-indigo-500 h-2.5 rounded-full"
                             data-progress="{{ $progress }}%"
                             style="--progress-width: {{ $progress }}%">
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>