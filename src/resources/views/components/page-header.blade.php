{{-- resources/views/components/page-header.blade.php --}}
@props([
    'title', 
    'description' => null, 
    'badge' => null,
    'breadcrumbs' => []
])

<section class="bg-gradient-to-b from-blue-50/50 to-white pt-12 pb-8 border-b border-gray-100 w-full">
    <div class="container mx-auto px-5">
        
        <!-- Breadcrumb (Chỉ hiển thị nếu có truyền mảng breadcrumbs) -->
        @if(!empty($breadcrumbs))
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4 flex-wrap">
                <a href="{{ route('categories.index') }}" class="hover:text-blue-600 transition-colors">Categories</a>
                @foreach($breadcrumbs as $label => $url)
                    <span class="text-gray-300">/</span>
                    @if(!$loop->last)
                        <a href="{{ $url }}" class="hover:text-blue-600 transition-colors">{{ $label }}</a>
                    @else
                        <span class="text-gray-800 font-medium">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <!-- Header Content -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                @if($badge)
                    <span class="inline-block py-1 px-2.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-md mb-2">
                        {{ $badge }}
                    </span>
                @endif
                
                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    {{ $title }}
                </h1>
                
                @if($description)
                    <p class="mt-2 text-gray-500 max-w-xl">
                        {{ $description }}
                    </p>
                @endif
            </div>
            @auth
                <div class="bg-white border border-gray-150 p-5 rounded-2xl shadow-sm min-w-[240px]">
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="text-gray-500 font-medium">Tiến độ bài học</span>
                        <span class="text-blue-600 font-bold">25% Hoàn thành</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2.5 rounded-full" style="width: 25%"></div>
                    </div>
                </div>
            @endauth
        </div>

    </div>
</section>