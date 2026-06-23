@extends('layouts.landing')

@php
    $photos = $property->photos;
    $cover = $photos->first();
    $video = $property->videoEmbed();
    $location = $property->locationLabel();

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Apartment',
        'name' => $property->title,
        'description' => $property->metaDescription(),
        'url' => url()->current(),
        'image' => $photos->pluck('url')->values()->all(),
        'numberOfRooms' => (int) $property->bedrooms,
        'occupancy' => ['@type' => 'QuantitativeValue', 'maxValue' => (int) $property->max_guests],
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $property->address,
            'addressLocality' => $property->city,
            'addressRegion' => $property->region,
            'addressCountry' => $property->country,
        ]),
        'geo' => ($property->lat && $property->lng) ? [
            '@type' => 'GeoCoordinates', 'latitude' => $property->lat, 'longitude' => $property->lng,
        ] : null,
        'amenityFeature' => $property->amenities->map(fn ($a) => [
            '@type' => 'LocationFeatureSpecification', 'name' => $a->name, 'value' => true,
        ])->values()->all(),
        'priceRange' => '€€',
    ]);

    $hasParking = $property->amenities->contains('slug', 'parcheggio-gratuito');
    $hasPets = $property->amenities->contains('slug', 'animali-ammessi');
    $ciTime = $property->check_in_time ?: '16:00';
    $coTime = $property->check_out_time ?: '10:00';

    $faqs = [
        ["Quanti ospiti può ospitare {$property->title}?", "Fino a {$property->max_guests} ospiti, con {$property->bedrooms} camere e {$property->bathrooms} bagno/i."],
        ['A che ora sono il check-in e il check-out?', "Check-in dalle {$ciTime}, check-out entro le {$coTime}. Il check-in è autonomo (self check-in)."],
        ['È disponibile il parcheggio?', $hasParking ? "Sì, l'immobile dispone di parcheggio privato all'interno della proprietà." : 'Contattaci per informazioni sul parcheggio in zona.'],
        ['Sono ammessi gli animali?', $hasPets ? 'Sì, gli animali domestici sono i benvenuti.' : 'Contattaci prima di prenotare per valutare la presenza di animali.'],
        ['Come funziona la prenotazione diretta?', 'Selezioni le date, vedi subito il prezzo e prenoti online con pagamento sicuro: nessuna commissione di intermediazione.'],
    ];

    $faqLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ], $faqs),
    ];
@endphp

@section('title', $property->metaTitle())
@section('description', $property->metaDescription())

@push('head')
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $property->metaTitle() }}">
    <meta property="og:description" content="{{ $property->metaDescription() }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="it_IT">
    @if ($cover)<meta property="og:image" content="{{ $cover->url }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

{{-- TOP BAR --}}
<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <div class="min-w-0">
            <div class="truncate text-base font-semibold">{{ $property->title }}</div>
            <div class="truncate text-xs text-slate-500">📍 {{ $location }}</div>
        </div>
        <a href="#prenota" class="landing-accent rounded-full px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-110">
            Prenota
        </a>
    </div>
</header>

{{-- GALLERY --}}
<section class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
    <div class="relative">
        {{-- Desktop grid --}}
        <div class="hidden h-[clamp(360px,52vh,520px)] grid-cols-4 grid-rows-2 gap-2 overflow-hidden rounded-2xl sm:grid">
            <button type="button" data-lb="0" class="group relative col-span-2 row-span-2 overflow-hidden">
                <img src="{{ $cover?->url }}" alt="{{ $property->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
            </button>
            @foreach ($photos->slice(1, 4) as $i => $photo)
                <button type="button" data-lb="{{ $i + 1 }}" class="group relative overflow-hidden">
                    <img src="{{ $photo->url }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                </button>
            @endforeach
        </div>

        {{-- Mobile single cover --}}
        <button type="button" data-lb="0" class="relative block aspect-[4/3] w-full overflow-hidden rounded-2xl sm:hidden">
            <img src="{{ $cover?->url }}" alt="{{ $property->title }}" class="h-full w-full object-cover">
        </button>

        {{-- Overlay buttons --}}
        <div class="pointer-events-none absolute inset-0">
            @if ($video)
                <button type="button" id="video-open"
                        class="pointer-events-auto absolute left-3 top-3 flex items-center gap-2 rounded-full bg-black/55 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-black/70">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    Video tour
                </button>
            @endif
            <button type="button" data-lb="0"
                    class="pointer-events-auto absolute bottom-3 right-3 flex items-center gap-2 rounded-full bg-white/95 px-4 py-2 text-sm font-semibold text-slate-900 shadow-md transition hover:bg-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Tutte le foto ({{ $photos->count() }})
            </button>
        </div>
    </div>
</section>

{{-- TITLE --}}
<section class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $property->title }}</h1>
    @if ($property->subtitle)<p class="mt-2 text-lg text-slate-600">{{ $property->subtitle }}</p>@endif
    <div class="mt-3 flex items-center gap-1.5 text-slate-600">
        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <span>{{ $location }}</span>
    </div>
    <div class="mt-5 flex flex-wrap gap-2">
        @foreach ([['👤', $property->max_guests, 'ospiti'], ['🛏', $property->bedrooms, 'camere'], ['🌙', $property->beds, 'letti'], ['🛁', $property->bathrooms, 'bagni'], ['⏱', $property->min_nights, 'notti min']] as [$ic, $n, $lab])
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-sm">
                <span>{{ $ic }}</span><strong>{{ $n }}</strong> <span class="text-slate-500">{{ $lab }}</span>
            </span>
        @endforeach
    </div>

    @if ($property->amenities->isNotEmpty())
        <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1">
            @foreach ($property->amenities->take(8) as $a)
                <span class="flex flex-none items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm">
                    <span>{{ $a->icon }}</span>{{ $a->name }}
                </span>
            @endforeach
        </div>
    @endif
</section>

{{-- BODY --}}
<section class="mx-auto mt-8 grid max-w-6xl gap-10 px-4 pb-10 sm:px-6 lg:grid-cols-3">
    {{-- LEFT --}}
    <div class="space-y-10 lg:col-span-2">
        @if ($property->description)
            <div>
                <h2 class="text-xl font-bold">L'immobile</h2>
                <div class="mt-3 leading-relaxed text-slate-700">{!! nl2br(e($property->description)) !!}</div>
            </div>
        @endif

        @if ($property->amenities->isNotEmpty())
            <div>
                <h2 class="text-xl font-bold">Cosa offre</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($property->amenities as $amenity)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                            <span class="text-lg">{{ $amenity->icon }}</span><span>{{ $amenity->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($video)
            <div>
                <h2 class="text-xl font-bold">Video tour</h2>
                <button type="button" id="video-open-2" class="group relative mt-4 block w-full overflow-hidden rounded-2xl">
                    <img src="{{ $cover?->url }}" alt="Video tour" class="aspect-video w-full object-cover brightness-90 transition group-hover:brightness-75">
                    <span class="absolute inset-0 grid place-items-center">
                        <span class="grid h-16 w-16 place-items-center rounded-full bg-white/90 shadow-lg transition group-hover:scale-110">
                            <svg class="h-7 w-7 translate-x-0.5 text-slate-900" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                    </span>
                </button>
            </div>
        @endif

        <div>
            <h2 class="text-xl font-bold">Scopri {{ $property->city }}</h2>
            <div class="mt-3 leading-relaxed text-slate-700">
                @if ($property->area_description)
                    {!! nl2br(e($property->area_description)) !!}
                @else
                    <p>{{ $property->title }} si trova a <strong>{{ $property->city }}</strong>{{ $property->region ? ' (' . $property->region . ')' : '' }}, in posizione ideale per vivere il territorio: spiagge, locali e attrazioni a pochi minuti.</p>
                @endif
            </div>
            @if ($property->lat && $property->lng)
                <iframe class="mt-4 h-72 w-full rounded-2xl border border-slate-200" loading="lazy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $property->lng - 0.01 }},{{ $property->lat - 0.008 }},{{ $property->lng + 0.01 }},{{ $property->lat + 0.008 }}&marker={{ $property->lat }},{{ $property->lng }}"></iframe>
            @endif
        </div>
    </div>

    {{-- RIGHT: booking card (desktop) --}}
    <aside id="prenota" class="hidden lg:block">
        <div class="js-booking sticky top-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
             data-quote-url="{{ route('properties.quote', $property) }}"
             data-checkout-url="{{ route('checkout.show', $property) }}">
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-bold">€{{ number_format($property->base_price, 0, ',', '.') }}</span>
                <span class="text-slate-500">/ notte</span>
            </div>
            <div class="mt-4 rounded-xl border border-slate-300 p-3">
                <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date</span>
                <input data-role="dates" readonly placeholder="Aggiungi le date"
                       class="mt-1 w-full cursor-pointer border-0 bg-transparent p-0 text-sm placeholder-slate-400 focus:ring-0">
            </div>
            <label class="mt-3 block rounded-xl border border-slate-300 p-3">
                <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Ospiti</span>
                <select data-role="guests" class="mt-1 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                    @for ($g = 1; $g <= $property->max_guests; $g++)<option value="{{ $g }}" @selected($g === 2)>{{ $g }} {{ $g === 1 ? 'ospite' : 'ospiti' }}</option>@endfor
                </select>
            </label>
            <input type="hidden" data-role="checkin"><input type="hidden" data-role="checkout">

            <p data-role="message" class="mt-3 hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>

            <div data-role="result" class="mt-4 hidden space-y-2 border-t border-slate-200 pt-4 text-sm">
                <div class="flex justify-between"><span class="text-slate-600" data-role="nightsLabel"></span><span data-role="accommodation"></span></div>
                <div class="flex justify-between"><span class="text-slate-600">Pulizia finale</span><span data-role="cleaning"></span></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><span>Totale</span><span data-role="total"></span></div>
            </div>

            <a data-role="book" href="#"
               class="landing-accent mt-4 block rounded-xl py-3.5 text-center text-sm font-semibold text-white opacity-50 pointer-events-none transition hover:brightness-110">
                Prenota ora
            </a>
            <p class="mt-2 text-center text-xs text-slate-400">Seleziona le date per vedere il prezzo</p>
        </div>
    </aside>
</section>

{{-- I NOSTRI SERVIZI --}}
<section class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        <h2 class="text-2xl font-bold tracking-tight">I nostri servizi</h2>
        <p class="mt-2 max-w-2xl text-slate-600">Gestiamo l'immobile in prima persona, per un soggiorno comodo e senza pensieri.</p>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['🔑', 'Self check-in', 'Arrivo autonomo e flessibile, con istruzioni dedicate.'],
                ['🧼', 'Pulizia professionale', 'Casa igienizzata e biancheria fresca a ogni arrivo.'],
                ['🛟', 'Assistenza dedicata', 'Ci siamo prima, durante e dopo il soggiorno.'],
                ['📶', 'Wi-Fi veloce', 'Connessione adatta anche allo smart working.'],
            ] as [$ic, $t, $d])
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="text-2xl">{{ $ic }}</div>
                    <h3 class="mt-3 font-semibold">{{ $t }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PERCHÉ PRENOTARE DIRETTO --}}
<section class="border-t border-slate-200">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        <h2 class="text-2xl font-bold tracking-tight">Perché prenotare diretto</h2>
        <div class="mt-8 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['Miglior prezzo', 'Nessuna commissione di intermediazione: paghi solo il soggiorno.'],
                ['Contatto diretto', 'Parli direttamente con chi gestisce la casa, senza filtri.'],
                ['Prenotazione sicura', 'Pagamento protetto e conferma immediata.'],
            ] as [$t, $d])
                <div class="flex gap-3">
                    <span class="landing-accent mt-0.5 grid h-7 w-7 flex-none place-items-center rounded-full text-sm text-white">✓</span>
                    <div>
                        <h3 class="font-semibold">{{ $t }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $d }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="border-t border-slate-200 bg-white pb-28 lg:pb-16">
    <div class="mx-auto max-w-3xl px-4 pt-14 sm:px-6">
        <h2 class="text-2xl font-bold tracking-tight">Domande frequenti</h2>
        <div class="mt-6 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200">
            @foreach ($faqs as $f)
                <details class="group px-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between py-4 font-medium [&::-webkit-details-marker]:hidden">
                        <span>{{ $f[0] }}</span>
                        <svg class="h-5 w-5 flex-none text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <p class="pb-4 text-slate-600">{{ $f[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- STICKY BOTTOM BAR (mobile) --}}
<div class="js-booking fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:hidden"
     data-quote-url="{{ route('properties.quote', $property) }}"
     data-checkout-url="{{ route('checkout.show', $property) }}"
     style="box-shadow:0 -8px 24px rgba(0,0,0,.08)">
    <p data-role="message" class="mb-2 hidden rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700"></p>
    <div class="flex items-stretch gap-2">
        <label class="flex-1 rounded-xl border border-slate-300 px-3 py-2">
            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Quando</span>
            <input data-role="dates" readonly placeholder="Aggiungi le date"
                   class="w-full cursor-pointer border-0 bg-transparent p-0 text-sm placeholder-slate-400 focus:ring-0">
        </label>
        <input type="hidden" data-role="checkin"><input type="hidden" data-role="checkout"><input type="hidden" data-role="guests" value="2">
        <a data-role="book" href="#"
           class="landing-accent flex items-center whitespace-nowrap rounded-xl px-5 text-sm font-semibold text-white opacity-50 pointer-events-none">
            Prenota
        </a>
    </div>
</div>

@push('scripts')
<script type="application/json" id="gallery-data">@json($photos->pluck('url'))</script>
@if ($video)<script type="application/json" id="video-data">@json($video)</script>@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const images = JSON.parse(document.getElementById('gallery-data').textContent || '[]');
    const videoEl = document.getElementById('video-data');
    const video = videoEl ? JSON.parse(videoEl.textContent) : null;

    /* ---------- Lightbox (immagini) ---------- */
    let idx = 0, overlay = null;
    function showImg() {
        overlay.querySelector('.lb-stage').innerHTML = `<img src="${images[idx]}" alt="">`;
        overlay.querySelector('.lb-count').textContent = (idx + 1) + ' / ' + images.length;
    }
    function openLightbox(i) {
        idx = i || 0;
        overlay = document.createElement('div');
        overlay.className = 'lb';
        overlay.innerHTML = `
            <button class="lb-btn lb-close" aria-label="Chiudi">✕</button>
            <button class="lb-btn lb-prev" aria-label="Precedente">‹</button>
            <div class="lb-stage"></div>
            <button class="lb-btn lb-next" aria-label="Successiva">›</button>
            <div class="lb-count"></div>`;
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        showImg();
        overlay.querySelector('.lb-close').onclick = close;
        overlay.querySelector('.lb-prev').onclick = (e) => { e.stopPropagation(); idx = (idx - 1 + images.length) % images.length; showImg(); };
        overlay.querySelector('.lb-next').onclick = (e) => { e.stopPropagation(); idx = (idx + 1) % images.length; showImg(); };
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
        document.addEventListener('keydown', onKey);
        // swipe
        let sx = 0;
        overlay.addEventListener('touchstart', (e) => sx = e.touches[0].clientX, { passive: true });
        overlay.addEventListener('touchend', (e) => {
            const dx = e.changedTouches[0].clientX - sx;
            if (Math.abs(dx) > 40) { idx = (idx + (dx < 0 ? 1 : -1) + images.length) % images.length; showImg(); }
        });
    }
    function onKey(e) {
        if (!overlay) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') { idx = (idx - 1 + images.length) % images.length; showImg(); }
        if (e.key === 'ArrowRight') { idx = (idx + 1) % images.length; showImg(); }
    }
    function close() {
        document.removeEventListener('keydown', onKey);
        overlay?.remove(); overlay = null;
        document.body.style.overflow = '';
    }
    document.querySelectorAll('[data-lb]').forEach((el) => {
        el.addEventListener('click', () => openLightbox(parseInt(el.dataset.lb, 10) || 0));
    });

    /* ---------- Video modal ---------- */
    function openVideo() {
        if (!video) return;
        const o = document.createElement('div');
        o.className = 'lb';
        let inner = '';
        if (video.type === 'youtube' || video.type === 'vimeo') {
            inner = `<iframe class="lb-frame" src="${video.embed}" allow="autoplay; fullscreen" allowfullscreen></iframe>`;
        } else if (video.type === 'file') {
            inner = `<video class="lb-video" src="${video.src}" controls autoplay playsinline></video>`;
        } else {
            window.open(video.url, '_blank'); return;
        }
        o.innerHTML = `<button class="lb-btn lb-close">✕</button><div class="lb-stage">${inner}</div>`;
        document.body.appendChild(o);
        document.body.style.overflow = 'hidden';
        const shut = () => { o.remove(); document.body.style.overflow = ''; };
        o.querySelector('.lb-close').onclick = shut;
        o.onclick = (e) => { if (e.target === o) shut(); };
    }
    document.getElementById('video-open')?.addEventListener('click', openVideo);
    document.getElementById('video-open-2')?.addEventListener('click', openVideo);

    /* ---------- Booking widgets ---------- */
    function fmt(n) { return '€' + Number(n).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    document.querySelectorAll('.js-booking').forEach((w) => {
        const q = (r) => w.querySelector(`[data-role="${r}"]`);
        const ci = q('checkin'), co = q('checkout'), gu = q('guests'), msg = q('message'), res = q('result'), tot = q('total'), book = q('book');
        const quoteUrl = w.dataset.quoteUrl, coUrl = w.dataset.checkoutUrl;

        function enableBook() {
            const p = new URLSearchParams({ check_in: ci.value, check_out: co.value, guests: (gu ? gu.value : 1) || 1 });
            book.href = coUrl + '?' + p.toString();
            book.classList.remove('opacity-50', 'pointer-events-none');
        }
        function disableBook() {
            book.href = '#';
            book.classList.add('opacity-50', 'pointer-events-none');
        }

        const datesEl = q('dates');
        if (datesEl && window.flatpickr) {
            window.flatpickr(datesEl, {
                mode: 'range',
                minDate: 'today',
                disableMobile: true,        // calendario flatpickr anche su smartphone
                altInput: true,
                altFormat: 'j M',
                dateFormat: 'Y-m-d',
                rangeSeparator: ' → ',
                onChange: (sel, _str, inst) => {
                    if (sel.length === 2) {
                        ci.value = inst.formatDate(sel[0], 'Y-m-d');
                        co.value = inst.formatDate(sel[1], 'Y-m-d');
                        quote();
                    } else {
                        ci.value = ''; co.value = '';
                        disableBook(); res?.classList.add('hidden'); msg.classList.add('hidden');
                    }
                },
            });
        }
        gu?.addEventListener('change', () => { if (ci.value && co.value) quote(); });

        async function quote() {
            if (!ci.value || !co.value) return;
            msg.classList.add('hidden');
            try {
                const r = await fetch(quoteUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ check_in: ci.value, check_out: co.value, guests: gu ? Number(gu.value) : 2 }),
                });
                const d = await r.json();
                if (!d.ok) { disableBook(); res?.classList.add('hidden'); msg.textContent = d.error; msg.classList.remove('hidden'); return; }
                const nl = q('nightsLabel'), acc = q('accommodation'), cl = q('cleaning');
                if (nl) nl.textContent = d.quote.nights + (d.quote.nights === 1 ? ' notte' : ' notti');
                if (acc) acc.textContent = fmt(d.quote.accommodation);
                if (cl) cl.textContent = fmt(d.quote.cleaning_fee);
                if (tot) tot.textContent = fmt(d.quote.total);
                res?.classList.remove('hidden');
                enableBook();
            } catch (e) {
                disableBook(); msg.textContent = 'Errore di connessione. Riprova.'; msg.classList.remove('hidden');
            }
        }
    });
});
</script>
@endpush
@endsection
