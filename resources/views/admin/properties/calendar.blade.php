@extends('admin.layout')

@section('title', 'Calendario')

@php
    $field = 'mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan [color-scheme:dark]';
    $lbl = 'block text-xs font-semibold uppercase tracking-wide text-white/60';
    $statusColor = ['available' => 'bg-cyan/15 text-cyan', 'booked' => 'bg-red-500/20 text-red-200', 'blocked' => 'bg-white/10 text-white/50'];
@endphp

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.properties.edit', $property) }}" class="text-sm text-white/60 hover:text-white">← {{ $property->title }}</a>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Calendario & prezzi</h1>
        </div>
    </div>

    {{-- Range editor --}}
    <form method="post" action="{{ route('admin.availability.update', $property) }}" class="glass mt-6 grid gap-4 rounded-2xl p-6 sm:grid-cols-5 sm:items-end">
        @csrf
        <div><label class="{{ $lbl }}">Dal</label><input type="date" name="from" required value="{{ $from->toDateString() }}" class="{{ $field }}"></div>
        <div><label class="{{ $lbl }}">Al</label><input type="date" name="to" required value="{{ $from->copy()->addDays(6)->toDateString() }}" class="{{ $field }}"></div>
        <div>
            <label class="{{ $lbl }}">Stato</label>
            <select name="status" class="{{ $field }}">
                <option value="available">Disponibile</option>
                <option value="blocked">Bloccato</option>
            </select>
        </div>
        <div><label class="{{ $lbl }}">Prezzo € (opz.)</label><input type="number" step="0.01" name="price" placeholder="invariato" class="{{ $field }}"></div>
        <button class="brand-gradient rounded-xl py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">Applica</button>
    </form>

    <p class="mt-4 text-xs text-white/50">Le notti già prenotate non vengono modificate da qui. Prossimi 120 giorni:</p>

    {{-- Day grid --}}
    <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-7">
        @php $cursor = $from->copy(); @endphp
        @while ($cursor->lte($to))
            @php $key = $cursor->toDateString(); $day = $days[$key] ?? null; $st = $day->status ?? 'available'; @endphp
            <div class="rounded-xl p-2.5 text-center {{ $statusColor[$st] ?? 'bg-white/5' }}">
                <div class="text-xs opacity-70">{{ $cursor->locale('it')->isoFormat('ddd D MMM') }}</div>
                <div class="mt-1 text-sm font-semibold">€{{ $day && $day->price ? number_format($day->price, 0, ',', '.') : number_format($property->base_price, 0, ',', '.') }}</div>
                <div class="text-[10px] uppercase tracking-wide opacity-70">{{ ['available' => 'libero', 'booked' => 'occupato', 'blocked' => 'bloccato'][$st] ?? $st }}</div>
            </div>
            @php $cursor->addDay(); @endphp
        @endwhile
    </div>
@endsection
