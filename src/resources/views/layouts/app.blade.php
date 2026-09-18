<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ===================== TITLE ===================== --}}
    <title>@yield('title', 'Học Từ Vựng Tiếng Anh Online') — {{ config('app.name', 'Vocafy') }}</title>

    {{-- ===================== META DESCRIPTION ===================== --}}
    <meta name="description" content="@yield('meta_description', 'Vocafy – Nền tảng học từ vựng tiếng Anh thông minh. Làm chủ 3000+ từ vựng TOEIC, IELTS, TOEFL qua phương pháp lặp lại ngắt quãng.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">

    {{-- ===================== CANONICAL URL ===================== --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- ===================== OPEN GRAPH ===================== --}}
    <meta property="og:site_name" content="{{ config('app.name', 'Vocafy') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', 'Học Từ Vựng Tiếng Anh Online') — {{ config('app.name', 'Vocafy') }}">
    <meta property="og:description" content="@yield('meta_description', 'Vocafy – Nền tảng học từ vựng tiếng Anh thông minh. Làm chủ 3000+ từ vựng TOEIC, IELTS, TOEFL qua phương pháp lặp lại ngắt quãng.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="vi_VN">

    {{-- ===================== TWITTER CARD ===================== --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Học Từ Vựng Tiếng Anh Online') — {{ config('app.name', 'Vocafy') }}">
    <meta name="twitter:description" content="@yield('meta_description', 'Vocafy – Nền tảng học từ vựng tiếng Anh thông minh. Làm chủ 3000+ từ vựng TOEIC, IELTS, TOEFL qua phương pháp lặp lại ngắt quãng.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-image.jpg'))">

    {{-- ===================== FAVICON ===================== --}}
    <link rel="icon" href="{{ asset('vocafy.ico') }}" type="image/x-icon" sizes="32x32">

    {{-- ===================== FONTS ===================== --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

    {{-- ===================== ASSETS ===================== --}}
    @vite(['resources/css/app.css','resources/js/app.js'])

    {{-- ===================== STRUCTURED DATA (JSON-LD) ===================== --}}
    @stack('schema')

    {{-- ===================== EXTRA HEAD ===================== --}}
    @stack('head')
</head>

<body class="bg-gray-50 flex flex-col min-h-screen">

    {{-- ===== SCROLL PROGRESS BAR ===== --}}
    <div id="scroll-progress"></div>

    {{-- ===== PAGE LOADER ===== --}}
    <div id="page-loader">
        <div class="text-center">
            <div class="loader-ring mx-auto mb-3"></div>
            <p class="text-xs font-semibold text-indigo-500 tracking-widest uppercase">Vocafy</p>
        </div>
    </div>

    @include('components.navbar')

    <main class="flex-grow">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('components.footer')

</body>
</html>