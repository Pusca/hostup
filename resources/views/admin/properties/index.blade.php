@extends('admin.layout')

@section('title', 'Immobili')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-extrabold tracking-tight">Immobili</h1>
        <a href="{{ route('admin.properties.create') }}" class="brand-gradient rounded-xl px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">+ Nuovo immobile</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($properties as $property)
            @php $cover = $property->coverPhoto ?? $property->photos->first(); @endphp
            <div class="glass overflow-hidden rounded-2xl">
                <div class="relative aspect-[4/3] bg-white/5">
                    @if ($cover)
                        <img src="{{ $cover->url }}" class="h-full w-full object-cover" alt="">
                    @else
                        <div class="grid h-full place-items-center text-white/40 text-sm">Nessuna foto</div>
                    @endif
                    <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs {{ $property->status === 'published' ? 'bg-cyan/20 text-cyan' : 'bg-navy-950/70 text-white/70' }} backdrop-blur">
                        {{ $property->status === 'published' ? 'Pubblicato' : 'Bozza' }}
                    </span>
                </div>
                <div class="p-4">
                    <div class="font-bold">{{ $property->title }}</div>
                    <div class="text-xs text-white/50">{{ $property->city }} · {{ $property->photos_count }} foto · €{{ number_format($property->base_price, 0, ',', '.') }}/notte</div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.properties.edit', $property) }}" class="flex-1 rounded-lg bg-white/10 py-2 text-center text-sm hover:bg-white/15">Modifica</a>
                        <a href="{{ route('admin.availability.index', $property) }}" class="flex-1 rounded-lg bg-white/10 py-2 text-center text-sm hover:bg-white/15">Calendario</a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-white/50">Nessun immobile ancora.</p>
        @endforelse
    </div>
@endsection
