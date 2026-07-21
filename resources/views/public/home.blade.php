@extends('layouts.public')

@section('title', 'HostUp — Gestione affitti brevi in automazione')
@section('meta_description', 'Gestiamo il tuo immobile in affitto breve: annunci, prezzi, calendari sincronizzati, prenotazione diretta e ospiti. Tecnologia nostra, zero pensieri per te.')

@section('content')

    {{-- HERO --}}
    <section class="relative min-h-[100svh] flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0">
            @if ($heroImage)
                <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-navy-950/85 via-navy-950/65 to-navy-950"></div>
            @else
                <div class="absolute inset-0 bg-gradient-to-b from-navy-900 via-navy-950 to-navy-950"></div>
                <div class="absolute -left-40 -top-40 h-[30rem] w-[30rem] rounded-full bg-cyan/10 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-32 h-[34rem] w-[34rem] rounded-full bg-blue/10 blur-3xl"></div>
            @endif
        </div>

        <div class="relative z-10 mx-auto w-full max-w-4xl px-5 pt-20 pb-10 text-center hu-reveal sm:pt-24">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan sm:text-sm sm:tracking-[0.25em]">Gestione affitti brevi</p>
            <h1 class="mt-3 text-[2.5rem] font-extrabold tracking-tight leading-[1.08] sm:mt-4 sm:text-7xl sm:leading-[1.05]">
                Il tuo immobile rende.<br class="hidden sm:block"> <span class="text-gradient">Noi lo gestiamo.</span>
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-base text-white/75 sm:mt-6 sm:text-lg">
                Annunci, prezzi, calendari, ospiti e pagamenti: gestiamo tutto noi, con un software costruito in casa.
                Tu ricevi le prenotazioni e il rendiconto, senza pensieri.
            </p>

            <div class="mx-auto mt-8 flex max-w-md flex-col items-stretch justify-center gap-3 sm:mt-10 sm:max-w-none sm:flex-row sm:items-center sm:gap-4">
                <a href="#contatti"
                   class="brand-gradient rounded-xl px-8 py-4 text-base font-semibold text-white shadow-2xl ring-1 ring-white/20 transition hover:opacity-90">
                    Richiedi una valutazione gratuita
                </a>
                <a href="#servizi"
                   class="glass rounded-xl px-8 py-4 text-base font-semibold text-white/90 transition hover:bg-white/10">
                    Scopri come lavoriamo
                </a>
            </div>

            <div class="mx-auto mt-10 grid max-w-2xl grid-cols-3 gap-2.5 text-center sm:mt-12 sm:gap-4">
                @foreach ([
                    ['0', 'doppie prenotazioni'],
                    ['24/7', 'prenotazioni online'],
                    ['1', 'calendario unico'],
                ] as [$num, $label])
                    <div class="glass rounded-2xl px-2 py-3 sm:px-3 sm:py-4">
                        <div class="text-xl font-extrabold text-gradient sm:text-3xl">{{ $num }}</div>
                        <div class="mt-1 text-[11px] leading-tight text-white/60 sm:text-sm">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 text-white/60 animate-bounce">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19 9-7 7-7-7"/></svg>
        </div>
    </section>

    {{-- SERVIZI --}}
    <section id="servizi" class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Cosa facciamo</p>
            <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Gestione completa, dall'annuncio all'ospite</h2>
            <p class="mt-4 text-white/65">Ci occupiamo di tutto quello che serve per far lavorare il tuo immobile, come farebbe un'azienda.</p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['📸', 'Annunci professionali', 'Foto, testi e schede curate per Airbnb, Booking e il sito dedicato al tuo immobile. La prima impressione decide la prenotazione.'],
                ['📈', 'Prezzi che seguono la stagione', 'Tariffe per notte aggiornate su stagionalità, weekend ed eventi. Niente prezzi fermi tutto l\'anno che lasciano soldi sul tavolo.'],
                ['🔄', 'Calendari sempre sincronizzati', 'Il nostro software allinea Airbnb, Booking e prenotazioni dirette su un calendario unico, in automatico. Mai un doppio check-in.'],
                ['🏷️', 'Prenotazione diretta senza commissioni', 'Ogni immobile ha la sua pagina con brand proprio, prenotazione e pagamento online. Le prenotazioni dirette non pagano commissioni alle piattaforme.'],
                ['💬', 'Gestione ospiti', 'Rispondiamo alle richieste, gestiamo check-in e assistenza durante il soggiorno. Gli ospiti contenti tornano e lasciano recensioni migliori.'],
                ['🧾', 'Trasparenza totale', 'Sai sempre quanto ha reso il tuo immobile: prenotazioni, incassi e calendario visibili in ogni momento, rendiconto chiaro.'],
            ] as [$icon, $title, $text])
                <div class="glass rounded-2xl p-8 transition hover:-translate-y-1 hover:ring-cyan/40">
                    <div class="text-3xl">{{ $icon }}</div>
                    <h3 class="mt-4 text-xl font-bold">{{ $title }}</h3>
                    <p class="mt-2 text-white/65">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- TECNOLOGIA --}}
    <section id="tecnologia" class="border-y border-white/10 bg-white/[0.02]">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="hu-reveal">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">La tecnologia</p>
                    <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Il software è nostro.<br>E si vede.</h2>
                    <p class="mt-4 text-white/65">
                        Non rivendiamo strumenti di altri: il channel manager che sincronizza i calendari,
                        il motore di prenotazione e le pagine dei singoli immobili sono costruiti da noi.
                        Questo significa automazione vera e nessun costo di licenza scaricato su di te.
                    </p>
                    <ul class="mt-8 space-y-4">
                        @foreach ([
                            ['Calendario unico', 'Airbnb, Booking e prenotazioni dirette convergono su un solo calendario, riconciliato in automatico ogni 15 minuti.'],
                            ['Pagina dedicata per ogni immobile', 'Brand, logo, foto, video e prenotazione online: ogni casa ha il suo sito, non una scheda anonima in un elenco.'],
                            ['Pagamenti online sicuri', 'Carte e wallet tramite Stripe, con conferma automatica della prenotazione.'],
                            ['Blocco anti-overbooking', 'Le date prenotate vengono bloccate all\'istante su tutti i canali collegati.'],
                        ] as [$title, $text])
                            <li class="flex gap-4">
                                <span class="brand-gradient mt-1 flex h-8 w-8 flex-none items-center justify-center rounded-full text-sm text-white">✓</span>
                                <div>
                                    <div class="font-semibold">{{ $title }}</div>
                                    <div class="text-sm text-white/60">{{ $text }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @foreach ([
                        ['15 min', 'frequenza di sincronizzazione dei calendari'],
                        ['24/7', 'il motore di prenotazione non chiude mai'],
                        ['0%', 'commissioni OTA sulle prenotazioni dirette'],
                        ['100%', 'visibilità su incassi e calendario'],
                    ] as [$num, $label])
                        <div class="glass rounded-2xl p-8 text-center">
                            <div class="text-4xl font-black text-gradient">{{ $num }}</div>
                            <p class="mt-3 text-sm text-white/60">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- COME FUNZIONA --}}
    <section id="come-funziona" class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Come funziona</p>
            <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Dal primo contatto alla prima prenotazione</h2>
        </div>

        <div class="mt-14 grid gap-8 md:grid-cols-4">
            @foreach ([
                ['01', 'Valutazione', 'Vediamo l\'immobile, la zona e il potenziale di ricavo. Ti diciamo con franchezza cosa aspettarti.'],
                ['02', 'Preparazione', 'Foto professionali, testi, prezzi e regole del soggiorno. Prepariamo l\'annuncio e la pagina dedicata.'],
                ['03', 'Pubblicazione', 'Online su Airbnb, Booking e sito di prenotazione diretta, con i calendari già sincronizzati tra loro.'],
                ['04', 'Gestione continua', 'Prenotazioni, ospiti, prezzi e assistenza: ce ne occupiamo noi. Tu segui tutto dal rendiconto.'],
            ] as [$num, $title, $text])
                <div class="glass rounded-2xl p-8">
                    <div class="text-5xl font-black text-gradient opacity-90">{{ $num }}</div>
                    <h3 class="mt-4 text-xl font-bold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-white/65">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- PORTFOLIO --}}
    @if ($properties->isNotEmpty())
        <section id="immobili" class="border-y border-white/10 bg-white/[0.02]">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 py-24">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Il portfolio</p>
                        <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Immobili che gestiamo</h2>
                    </div>
                    <p class="max-w-sm text-white/60">Ogni immobile ha la sua pagina dedicata con prenotazione diretta. Guarda come presenteremmo il tuo.</p>
                </div>

                <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($properties as $property)
                        @include('public.partials.property-card', ['property' => $property])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ PROPRIETARI --}}
    <section id="faq" class="mx-auto max-w-4xl px-5 sm:px-8 py-24">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Domande frequenti</p>
            <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Quello che i proprietari ci chiedono</h2>
        </div>

        <div class="mt-12 space-y-4">
            @foreach ([
                ['Quanto costa la gestione?', 'Lavoriamo con una commissione percentuale sulle prenotazioni andate a buon fine, concordata prima di iniziare. Nessun costo fisso: se il tuo immobile non incassa, noi non incassiamo.'],
                ['Posso continuare a usare la casa quando voglio?', 'Sì. Basta dircelo (o bloccarlo a calendario) e quelle date non saranno prenotabili su nessun canale. L\'immobile resta tuo, anche nell\'uso.'],
                ['Su quali piattaforme viene pubblicato l\'immobile?', 'Airbnb, Booking.com e la pagina di prenotazione diretta che creiamo apposta per il tuo immobile. I calendari sono sincronizzati in automatico, quindi niente rischio di doppie prenotazioni.'],
                ['Come vedo quanto sta rendendo?', 'Hai visibilità completa su prenotazioni, incassi e calendario, con un rendiconto periodico chiaro: quanto è entrato, quali costi, quanto ti spetta.'],
                ['Sono vincolato a lungo?', 'No. Crediamo di doverci guadagnare la fiducia con i risultati, non con i vincoli contrattuali. Le condizioni di uscita sono semplici e nero su bianco.'],
            ] as [$q, $a])
                <details class="glass group rounded-2xl">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 p-6 font-semibold marker:content-none">
                        {{ $q }}
                        <span class="text-cyan transition group-open:rotate-45 text-xl leading-none">+</span>
                    </summary>
                    <p class="px-6 pb-6 text-white/65">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- CONTATTI / LEAD FORM --}}
    <section id="contatti" class="relative overflow-hidden border-t border-white/10">
        <div class="absolute inset-0 bg-navy-950"></div>
        <div class="absolute -top-32 left-1/4 h-96 w-96 rounded-full bg-cyan/10 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-blue/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-5 sm:px-8 py-24">
            <div class="grid items-start gap-12 lg:grid-cols-2">
                <div class="hu-reveal">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">Parliamone</p>
                    <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Hai un immobile?<br><span class="text-gradient">Scopri quanto può rendere.</span></h2>
                    <p class="mt-4 max-w-md text-white/65">
                        Raccontaci del tuo immobile: ti ricontattiamo entro 24 ore con una prima valutazione
                        gratuita e senza impegno.
                    </p>
                    <ul class="mt-8 space-y-3 text-white/75">
                        <li class="flex items-center gap-3"><span class="text-cyan">✉</span> info@hostup.it</li>
                    </ul>
                </div>

                <form method="post" action="{{ route('owners.lead') }}" class="glass rounded-2xl p-6 sm:p-8">
                    @csrf
                    {{-- Honeypot anti-spam --}}
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-white/60">Nome e cognome *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white placeholder-white/40 focus:border-cyan focus:ring-2 focus:ring-cyan/40">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-white/60">Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white placeholder-white/40 focus:border-cyan focus:ring-2 focus:ring-cyan/40">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-white/60">Telefono</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                   class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white placeholder-white/40 focus:border-cyan focus:ring-2 focus:ring-cyan/40">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-white/60">Dove si trova l'immobile?</label>
                            <input type="text" name="city" value="{{ old('city') }}" placeholder="Città o zona"
                                   class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white placeholder-white/40 focus:border-cyan focus:ring-2 focus:ring-cyan/40">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-white/60">Tipo di immobile</label>
                            <select name="property_type"
                                    class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white focus:border-cyan focus:ring-2 focus:ring-cyan/40 [color-scheme:dark]">
                                <option value="">Seleziona…</option>
                                @foreach (['Appartamento', 'Villa', 'Casa vacanze', 'B&B', 'Altro'] as $type)
                                    <option value="{{ $type }}" @selected(old('property_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-white/60">Raccontaci qualcosa in più</label>
                            <textarea name="message" rows="4" placeholder="Quante camere, se è già in affitto breve, cosa vorresti migliorare…"
                                      class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white placeholder-white/40 focus:border-cyan focus:ring-2 focus:ring-cyan/40">{{ old('message') }}</textarea>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button type="submit"
                            class="brand-gradient mt-6 w-full rounded-xl px-6 py-4 text-base font-semibold text-white ring-1 ring-white/20 transition hover:opacity-90">
                        Invia la richiesta
                    </button>
                    <p class="mt-3 text-center text-xs text-white/40">Valutazione gratuita, nessun impegno.</p>
                </form>
            </div>
        </div>
    </section>

@endsection
