<!doctype html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'Gestione') — HostUp CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-white/90 bg-navy-950 min-h-screen">
    <div class="hu-glow"></div>

    {{-- Topbar --}}
    <header class="glass sticky top-0 z-40 border-b border-white/10">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a href="{{ route('admin.dashboard') }}"><x-logo :size="18" :badge="38" /></a>
                    <nav class="hidden items-center gap-1 md:flex">
                        @php $r = request()->routeIs(...); @endphp
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white' }}">Dashboard</a>
                        <a href="{{ route('admin.properties.index') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('admin.properties.*') || request()->routeIs('admin.availability.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white' }}">Immobili</a>
                        @php $newLeads = \App\Models\OwnerLead::where('status', 'new')->count(); @endphp
                        <a href="{{ route('admin.leads.index') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('admin.leads.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white' }}">
                            Richieste
                            @if ($newLeads > 0)
                                <span class="ml-1 rounded-full bg-cyan/20 px-2 py-0.5 text-xs font-semibold text-cyan">{{ $newLeads }}</span>
                            @endif
                        </a>
                        <a href="{{ route('home') }}" target="_blank" class="rounded-lg px-3 py-2 text-sm text-white/70 hover:text-white">Sito ↗</a>
                    </nav>
                </div>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-white/15 px-3 py-2 text-sm text-white/80 hover:bg-white/10">Esci</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 sm:px-8 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-cyan/30 bg-cyan/10 px-4 py-3 text-sm text-cyan">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
