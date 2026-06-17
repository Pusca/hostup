@extends('layouts.public')

@section('title', 'Prenota — ' . $property->title)

@php
    $field = 'mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan [color-scheme:dark]';
    $lbl = 'block text-xs font-semibold uppercase tracking-wide text-white/60';
    $cover = $property->coverPhoto ?? $property->photos->first();
@endphp

@section('content')
<section class="mx-auto max-w-5xl px-5 sm:px-8 pt-28 pb-16">
    <a href="{{ route('properties.show', $property) }}" class="text-sm text-white/60 hover:text-white">← Torna all'immobile</a>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Completa la prenotazione</h1>

    <div class="mt-8 grid gap-8 lg:grid-cols-5">
        {{-- Guest form --}}
        <form method="post" action="{{ route('checkout.store', $property) }}" class="glass rounded-3xl p-6 lg:col-span-3 space-y-4">
            @csrf
            <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
            <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
            <input type="hidden" name="guests" value="{{ $guests }}">

            <h2 class="font-bold">I tuoi dati</h2>
            <div>
                <label class="{{ $lbl }}">Nome e cognome *</label>
                <input name="name" required value="{{ old('name') }}" class="{{ $field }}">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $lbl }}">Email *</label>
                    <input type="email" name="email" required value="{{ old('email') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Telefono</label>
                    <input name="phone" value="{{ old('phone') }}" class="{{ $field }}">
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">Note (opzionale)</label>
                <textarea name="note" rows="3" class="{{ $field }}">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="brand-gradient w-full rounded-xl py-3.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">
                Vai al pagamento →
            </button>
            <p class="text-center text-xs text-white/50">Pagamento sicuro tramite Stripe.</p>
        </form>

        {{-- Summary --}}
        <aside class="lg:col-span-2">
            <div class="glass sticky top-28 rounded-3xl p-6">
                <div class="flex gap-4">
                    @if ($cover)
                        <img src="{{ $cover->url }}" class="h-20 w-24 flex-none rounded-xl object-cover" alt="">
                    @endif
                    <div>
                        <div class="font-bold leading-tight">{{ $property->title }}</div>
                        <div class="text-xs text-white/50">{{ $property->city }}</div>
                    </div>
                </div>

                <div class="mt-5 space-y-2 border-t border-white/10 pt-5 text-sm">
                    <div class="flex justify-between"><span class="text-white/60">Check-in</span><span>{{ $checkIn->locale('it')->isoFormat('ddd D MMM Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-white/60">Check-out</span><span>{{ $checkOut->locale('it')->isoFormat('ddd D MMM Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-white/60">Ospiti</span><span>{{ $guests }}</span></div>
                </div>

                <div class="mt-5 space-y-2 border-t border-white/10 pt-5 text-sm">
                    <div class="flex justify-between"><span class="text-white/60">{{ $quote['nights'] }} notti</span><span>€{{ number_format($quote['accommodation'], 2, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-white/60">Pulizia finale</span><span>€{{ number_format($quote['cleaning_fee'], 2, ',', '.') }}</span></div>
                    <div class="flex justify-between border-t border-white/10 pt-3 text-base font-bold"><span>Totale</span><span class="text-gradient">€{{ number_format($quote['total'], 2, ',', '.') }}</span></div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection
