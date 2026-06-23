<!doctype html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <title>@yield('title')</title>
    <meta name="description" content="@yield('description')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta name="robots" content="index, follow">

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing font-sans antialiased text-slate-900">
    @if (session('error'))
        <div class="fixed top-4 left-1/2 z-[70] -translate-x-1/2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-medium text-white shadow-lg">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')

    @stack('scripts')
</body>
</html>
