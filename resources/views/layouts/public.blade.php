<!doctype html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'HostUp — Gestione affitti brevi in automazione')</title>
    <meta name="description" content="@yield('meta_description', 'Gestiamo affitti brevi con tecnologia nostra: annunci, prezzi, calendari sincronizzati, prenotazione diretta e ospiti. Il tuo immobile rende, tu non ci pensi.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-white/90 bg-navy-950">

    <div class="hu-glow"></div>

    {{-- NAV --}}
    <header id="nav" class="fixed inset-x-0 top-0 z-50 transition-all duration-300 border-b border-transparent">
        <nav class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="flex h-20 items-center justify-between">
                <a href="{{ route('home') }}"><x-logo :size="20" :badge="42" /></a>

                <div class="hidden items-center gap-2 md:flex">
                    <a href="{{ route('home') }}#servizi" class="rounded-xl px-3 py-2 text-sm text-white/70 transition hover:bg-white/6 hover:text-white">Servizi</a>
                    <a href="{{ route('home') }}#tecnologia" class="rounded-xl px-3 py-2 text-sm text-white/70 transition hover:bg-white/6 hover:text-white">Tecnologia</a>
                    <a href="{{ route('home') }}#come-funziona" class="rounded-xl px-3 py-2 text-sm text-white/70 transition hover:bg-white/6 hover:text-white">Come funziona</a>
                    <a href="{{ route('home') }}#immobili" class="rounded-xl px-3 py-2 text-sm text-white/70 transition hover:bg-white/6 hover:text-white">Immobili</a>
                </div>

                <a href="{{ route('home') }}#contatti"
                   class="brand-gradient rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg ring-1 ring-white/20 transition hover:opacity-90">
                    Valutazione gratuita
                </a>
            </div>
        </nav>
    </header>

    @if (session('error') || session('status'))
        <div class="fixed top-24 left-1/2 z-[60] -translate-x-1/2 px-4">
            <div class="glass rounded-xl px-5 py-3 text-sm {{ session('error') ? 'text-red-200' : 'text-cyan' }} shadow-xl">
                {{ session('error') ?? session('status') }}
            </div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="border-t border-white/10 bg-navy-950/60">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 py-16">
            <div class="grid gap-10 md:grid-cols-3">
                <div>
                    <x-logo :size="22" :badge="44" />
                    <p class="mt-4 max-w-xs text-sm text-white/60">
                        Gestione professionale di affitti brevi, con tecnologia costruita in casa. Il tuo immobile rende, tu non ci pensi.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-white/50">Esplora</h4>
                    <ul class="mt-4 space-y-2 text-sm text-white/80">
                        <li><a href="{{ route('home') }}#servizi" class="transition hover:text-white">Servizi</a></li>
                        <li><a href="{{ route('home') }}#come-funziona" class="transition hover:text-white">Come funziona</a></li>
                        <li><a href="{{ route('home') }}#contatti" class="transition hover:text-white">Valutazione gratuita</a></li>
                        <li><a href="{{ route('properties.index') }}" class="transition hover:text-white">Prenota un soggiorno</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-white/50">Contatti</h4>
                    <ul class="mt-4 space-y-2 text-sm text-white/70">
                        <li>info@hostup.it</li>
                        <li>+39 000 000 0000</li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 border-t border-white/10 pt-6 text-xs text-white/40">
                © {{ date('Y') }} HostUp. Tutti i diritti riservati.
            </div>
        </div>
    </footer>

    <script>
        const nav = document.getElementById('nav');
        const onScroll = () => {
            if (window.scrollY > 40) {
                nav.classList.add('glass', 'border-white/10');
                nav.classList.remove('border-transparent');
            } else {
                nav.classList.remove('glass', 'border-white/10');
                nav.classList.add('border-transparent');
            }
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    </script>
    @stack('scripts')
</body>
</html>
