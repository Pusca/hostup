@extends('layouts.public')

@section('content')

    {{-- HERO --}}
    <section class="relative min-h-[100svh] flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://picsum.photos/seed/hostup-hero/1920/1200" alt="" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-navy-950/80 via-navy-950/55 to-navy-950"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-4xl px-5 text-center hu-reveal">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan">Affitti brevi gestiti a 360°</p>
            <h1 class="mt-4 text-5xl font-extrabold tracking-tight leading-[1.05] sm:text-7xl">
                Dormi dove gli altri<br><span class="text-gradient">sognano di tornare</span>
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-lg text-white/75">
                Case e ville selezionate a mano. Foto reali, esperienze autentiche, prenotazione diretta senza intermediari.
            </p>

            {{-- Booking-focused search bar --}}
            <form action="{{ route('properties.index') }}" method="get"
                  class="glass mx-auto mt-10 flex max-w-3xl flex-col gap-3 rounded-2xl p-3 text-left shadow-2xl sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="block px-3 pt-1 text-xs font-semibold uppercase tracking-wide text-white/60">Check-in</label>
                    <input type="date" name="check_in" min="{{ date('Y-m-d') }}"
                           class="w-full rounded-xl border-0 bg-transparent px-3 py-2 text-sm text-white placeholder-white/40 focus:ring-2 focus:ring-cyan [color-scheme:dark]">
                </div>
                <div class="flex-1 sm:border-l sm:border-white/15">
                    <label class="block px-3 pt-1 text-xs font-semibold uppercase tracking-wide text-white/60">Check-out</label>
                    <input type="date" name="check_out" min="{{ date('Y-m-d') }}"
                           class="w-full rounded-xl border-0 bg-transparent px-3 py-2 text-sm text-white focus:ring-2 focus:ring-cyan [color-scheme:dark]">
                </div>
                <div class="sm:w-32 sm:border-l sm:border-white/15">
                    <label class="block px-3 pt-1 text-xs font-semibold uppercase tracking-wide text-white/60">Ospiti</label>
                    <input type="number" name="guests" min="1" value="2"
                           class="w-full rounded-xl border-0 bg-transparent px-3 py-2 text-sm text-white focus:ring-2 focus:ring-cyan">
                </div>
                <button type="submit"
                        class="brand-gradient rounded-xl px-6 py-3 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:opacity-90">
                    Cerca
                </button>
            </form>
        </div>

        <div class="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 text-white/60 animate-bounce">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19 9-7 7-7-7"/></svg>
        </div>
    </section>

    {{-- IMMOBILI --}}
    <section id="immobili" class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">La collezione</p>
                <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">I nostri immobili</h2>
            </div>
            <p class="max-w-sm text-white/60">Ogni casa è scelta e curata da noi. Niente sorprese: quello che vedi è quello che vivrai.</p>
        </div>

        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($properties as $property)
                @include('public.partials.property-card', ['property' => $property])
            @endforeach
        </div>
    </section>

    {{-- COME LAVORIAMO --}}
    <section id="come-lavoriamo" class="border-y border-white/10 bg-white/[0.02]">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Il metodo HostUp</p>
                <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Come lavoriamo</h2>
                <p class="mt-4 text-white/65">Gestiamo i nostri immobili in prima persona, sincronizzati su tutte le piattaforme. Tu prenoti diretto e risparmi.</p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['01', 'Selezione curata', 'Scegliamo e arrediamo personalmente ogni casa per offrirti comfort e autenticità.'],
                    ['02', 'Disponibilità in tempo reale', 'Calendari sincronizzati tra Airbnb, Booking e il nostro sito: mai un doppio check-in.'],
                    ['03', 'Prenotazione diretta', 'Prenoti qui senza commissioni di intermediazione, con assistenza diretta da noi.'],
                ] as [$num, $title, $text])
                    <div class="glass rounded-2xl p-8">
                        <div class="text-5xl font-black text-gradient opacity-90">{{ $num }}</div>
                        <h3 class="mt-4 text-xl font-bold">{{ $title }}</h3>
                        <p class="mt-2 text-white/65">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ESPERIENZE --}}
    <section id="esperienze" class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div class="hu-reveal">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Oltre il soggiorno</p>
                <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Esperienze che restano</h2>
                <p class="mt-4 text-white/65">
                    Tour in barca tra le grotte, degustazioni di vini locali, cene tra gli ulivi. Ti aiutiamo a vivere il territorio come chi ci abita.
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach (['Tour in barca al tramonto', 'Degustazioni e cantine', 'Lezioni di cucina', 'Noleggio bici e e-bike'] as $exp)
                        <li class="flex items-center gap-3">
                            <span class="brand-gradient flex h-8 w-8 items-center justify-center rounded-full text-white text-sm">✓</span>
                            <span class="font-medium">{{ $exp }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <img src="https://picsum.photos/seed/exp-1/600/800" class="h-full w-full rounded-2xl object-cover ring-1 ring-white/10" alt="">
                <img src="https://picsum.photos/seed/exp-2/600/800" class="mt-8 h-full w-full rounded-2xl object-cover ring-1 ring-white/10" alt="">
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative overflow-hidden border-t border-white/10">
        <img src="https://picsum.photos/seed/hostup-cta/1920/700" class="absolute inset-0 h-full w-full object-cover" alt="">
        <div class="absolute inset-0 bg-navy-950/80"></div>
        <div class="relative mx-auto max-w-3xl px-5 py-24 text-center">
            <h2 class="text-4xl font-extrabold tracking-tight sm:text-5xl">Pronto a partire?</h2>
            <p class="mt-4 text-white/75">Scegli la tua casa e prenota in pochi minuti. Diretto, sicuro, senza commissioni nascoste.</p>
            <a href="#immobili" class="brand-gradient mt-8 inline-block rounded-xl px-8 py-4 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:opacity-90">
                Scopri gli immobili
            </a>
        </div>
    </section>

@endsection
