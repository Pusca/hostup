@extends('layouts.public')

@section('title', $property->title . ' — HostUp')
@section('meta_description', $property->subtitle)

@section('content')
    @php
        $photos = $property->photos;
        $hero = $photos->first();
    @endphp

    {{-- GALLERY --}}
    <section class="pt-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-2 overflow-hidden rounded-3xl ring-1 ring-white/10 sm:grid-cols-4 sm:grid-rows-2 sm:h-[60vh]">
                <div class="sm:col-span-2 sm:row-span-2">
                    <img src="{{ $hero?->url }}" alt="{{ $property->title }}" class="h-64 w-full object-cover sm:h-full">
                </div>
                @foreach ($photos->slice(1, 4) as $photo)
                    <div class="hidden sm:block">
                        <img src="{{ $photo->url }}" alt="" class="h-full w-full object-cover">
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- BODY --}}
    <section class="mx-auto max-w-7xl px-5 sm:px-8 py-12">
        <div class="grid gap-12 lg:grid-cols-3">

            {{-- LEFT: details --}}
            <div class="lg:col-span-2">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">{{ $property->city }}, {{ $property->region }}</p>
                <h1 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $property->title }}</h1>
                <p class="mt-2 text-lg text-white/65">{{ $property->subtitle }}</p>

                <div class="mt-6 flex flex-wrap gap-6 border-y border-white/10 py-5 text-sm text-white/80">
                    <span>👤 <strong class="text-white">{{ $property->max_guests }}</strong> ospiti</span>
                    <span>🛏 <strong class="text-white">{{ $property->bedrooms }}</strong> camere</span>
                    <span>🌙 <strong class="text-white">{{ $property->beds }}</strong> letti</span>
                    <span>🛁 <strong class="text-white">{{ $property->bathrooms }}</strong> bagni</span>
                    <span>⏱ Min <strong class="text-white">{{ $property->min_nights }}</strong> notti</span>
                </div>

                <div class="mt-8 max-w-none leading-relaxed text-white/80">
                    {!! nl2br(e($property->description)) !!}
                </div>

                @if ($property->amenities->isNotEmpty())
                    <h2 class="mt-12 text-2xl font-bold">Servizi</h2>
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($property->amenities as $amenity)
                            <div class="flex items-center gap-3 rounded-xl glass px-4 py-3 text-sm">
                                <span class="text-lg">{{ $amenity->icon }}</span>
                                <span>{{ $amenity->name }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- RIGHT: booking widget (sticky) --}}
            <div class="lg:col-span-1">
                <div class="glass sticky top-28 rounded-3xl p-6 shadow-2xl"
                     id="booking-widget"
                     data-quote-url="{{ route('properties.quote', $property) }}"
                     data-checkout-url="{{ route('checkout.show', $property) }}"
                     data-max-guests="{{ $property->max_guests }}">
                    <div class="flex items-baseline justify-between">
                        <div><span class="text-3xl font-extrabold">€{{ number_format($property->base_price, 0, ',', '.') }}</span> <span class="text-white/60">/ notte</span></div>
                        <div class="text-sm text-white/60">Min {{ $property->min_nights }} notti</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-white/60">Check-in</label>
                                <input type="date" id="bw-checkin" min="{{ date('Y-m-d') }}"
                                       class="mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan [color-scheme:dark]">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-white/60">Check-out</label>
                                <input type="date" id="bw-checkout" min="{{ date('Y-m-d') }}"
                                       class="mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan [color-scheme:dark]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-white/60">Ospiti</label>
                            <input type="number" id="bw-guests" min="1" max="{{ $property->max_guests }}" value="2"
                                   class="mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan">
                        </div>
                    </div>

                    <button id="bw-submit"
                            class="brand-gradient mt-5 w-full rounded-xl py-3.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:opacity-90 disabled:opacity-50">
                        Verifica disponibilità
                    </button>

                    <div id="bw-message" class="mt-3 hidden rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-300"></div>

                    {{-- Price breakdown --}}
                    <div id="bw-quote" class="mt-5 hidden space-y-2 border-t border-white/10 pt-5 text-sm">
                        <div class="flex justify-between"><span id="bw-nights-label" class="text-white/65"></span><span id="bw-accommodation"></span></div>
                        <div class="flex justify-between"><span class="text-white/65">Pulizia finale</span><span id="bw-cleaning"></span></div>
                        <div class="flex justify-between border-t border-white/10 pt-3 text-base font-bold"><span>Totale</span><span id="bw-total" class="text-gradient"></span></div>
                        <button id="bw-book"
                                class="mt-4 w-full rounded-xl bg-white/10 py-3.5 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/15">
                            Prenota ora
                        </button>
                        <p class="text-center text-xs text-white/50">Non ti verrà addebitato nulla in questa fase.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
        (function () {
            const widget = document.getElementById('booking-widget');
            if (!widget) return;

            const url = widget.dataset.quoteUrl;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const fmt = (n) => '€' + Number(n).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const elIn = document.getElementById('bw-checkin');
            const elOut = document.getElementById('bw-checkout');
            const elGuests = document.getElementById('bw-guests');
            const btn = document.getElementById('bw-submit');
            const msg = document.getElementById('bw-message');
            const quoteBox = document.getElementById('bw-quote');

            elIn.addEventListener('change', () => {
                if (elIn.value) {
                    const next = new Date(elIn.value);
                    next.setDate(next.getDate() + 1);
                    elOut.min = next.toISOString().slice(0, 10);
                    if (elOut.value && elOut.value <= elIn.value) elOut.value = elOut.min;
                }
            });

            function showError(text) {
                msg.textContent = text;
                msg.classList.remove('hidden');
                quoteBox.classList.add('hidden');
            }

            btn.addEventListener('click', async () => {
                msg.classList.add('hidden');
                if (!elIn.value || !elOut.value) { showError('Seleziona le date di check-in e check-out.'); return; }

                btn.disabled = true;
                btn.textContent = 'Verifica in corso...';

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ check_in: elIn.value, check_out: elOut.value, guests: Number(elGuests.value || 1) }),
                    });
                    const data = await res.json();

                    if (!data.ok) { showError(data.error || 'Date non disponibili.'); return; }

                    const q = data.quote;
                    document.getElementById('bw-nights-label').textContent = q.nights + (q.nights === 1 ? ' notte' : ' notti');
                    document.getElementById('bw-accommodation').textContent = fmt(q.accommodation);
                    document.getElementById('bw-cleaning').textContent = fmt(q.cleaning_fee);
                    document.getElementById('bw-total').textContent = fmt(q.total);
                    quoteBox.classList.remove('hidden');
                    msg.classList.add('hidden');
                } catch (e) {
                    showError('Errore di connessione. Riprova.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Verifica disponibilità';
                }
            });

            document.getElementById('bw-book').addEventListener('click', () => {
                if (!elIn.value || !elOut.value) return;
                const params = new URLSearchParams({ check_in: elIn.value, check_out: elOut.value, guests: elGuests.value || 1 });
                window.location.href = widget.dataset.checkoutUrl + '?' + params.toString();
            });
        })();
    </script>
    @endpush
@endsection
