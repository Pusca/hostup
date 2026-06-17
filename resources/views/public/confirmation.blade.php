@extends('layouts.public')

@section('title', 'Prenotazione confermata — HostUp')

@section('content')
<section class="mx-auto max-w-2xl px-5 pt-32 pb-24 text-center">
    <div class="brand-gradient mx-auto grid h-16 w-16 place-items-center rounded-full text-3xl text-white ring-1 ring-white/20">✓</div>
    <h1 class="mt-6 text-4xl font-extrabold tracking-tight">Prenotazione confermata!</h1>
    <p class="mt-3 text-white/70">Grazie {{ $booking->guest?->name }}, ti abbiamo inviato i dettagli via email.</p>

    <div class="glass mt-8 rounded-3xl p-6 text-left">
        <div class="flex items-center justify-between">
            <span class="text-white/60 text-sm">Codice prenotazione</span>
            <span class="font-bold text-gradient">{{ $booking->reference }}</span>
        </div>
        <div class="mt-4 space-y-2 border-t border-white/10 pt-4 text-sm">
            <div class="flex justify-between"><span class="text-white/60">Immobile</span><span>{{ $booking->property?->title }}</span></div>
            <div class="flex justify-between"><span class="text-white/60">Check-in</span><span>{{ $booking->check_in->locale('it')->isoFormat('ddd D MMM Y') }}</span></div>
            <div class="flex justify-between"><span class="text-white/60">Check-out</span><span>{{ $booking->check_out->locale('it')->isoFormat('ddd D MMM Y') }}</span></div>
            <div class="flex justify-between"><span class="text-white/60">Ospiti</span><span>{{ $booking->guests_count }}</span></div>
            <div class="flex justify-between border-t border-white/10 pt-3 text-base font-bold"><span>Totale</span><span>€{{ number_format($booking->total_amount, 2, ',', '.') }}</span></div>
        </div>
        @if ($booking->payment_status !== 'paid')
            <p class="mt-4 rounded-xl bg-white/5 px-4 py-3 text-xs text-white/60">Stato pagamento: {{ $booking->payment_status }}.</p>
        @endif
    </div>

    <a href="{{ route('home') }}" class="mt-8 inline-block rounded-xl bg-white/10 px-6 py-3 text-sm font-semibold hover:bg-white/15">Torna alla home</a>
</section>
@endsection
