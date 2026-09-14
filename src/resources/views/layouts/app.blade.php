<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="icon" href="{{ asset('vocafy.ico') }}" type="image/x-icon">
</head>

<body class="bg-gray-50 flex flex-col min-h-screen">

    @include('components.navbar')

    <main class="flex-grow">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('components.footer')

</body>
</html>