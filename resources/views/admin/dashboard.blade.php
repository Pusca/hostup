@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-extrabold tracking-tight">Dashboard</h1>
        <a href="{{ route('admin.properties.create') }}" class="brand-gradient rounded-xl px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">+ Nuovo immobile</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Immobili', $stats['properties']],
            ['Pubblicati', $stats['published']],
            ['Prenotazioni', $stats['bookings']],
            ['In arrivo', $stats['upcoming']],
        ] as [$label, $value])
            <div class="glass rounded-2xl p-5">
                <div class="text-sm text-white/60">{{ $label }}</div>
                <div class="mt-1 text-3xl font-extrabold">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-bold">Immobili</h2>
            <div class="mt-4 space-y-2">
                @forelse ($properties as $property)
                    <a href="{{ route('admin.properties.edit', $property) }}" class="flex items-center justify-between rounded-xl bg-white/5 px-4 py-3 hover:bg-white/10">
                        <div>
                            <div class="font-medium">{{ $property->title }}</div>
                            <div class="text-xs text-white/50">{{ $property->city }} · {{ $property->photos_count }} foto</div>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs {{ $property->status === 'published' ? 'bg-cyan/15 text-cyan' : 'bg-white/10 text-white/60' }}">
                            {{ $property->status === 'published' ? 'Pubblicato' : 'Bozza' }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-white/50">Nessun immobile. Creane uno.</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-bold">Ultime prenotazioni</h2>
            <div class="mt-4 space-y-2">
                @forelse ($recentBookings as $b)
                    <div class="flex items-center justify-between rounded-xl bg-white/5 px-4 py-3">
                        <div>
                            <div class="font-medium">{{ $b->reference }} · {{ $b->property?->title }}</div>
                            <div class="text-xs text-white/50">{{ $b->check_in?->format('d/m/Y') }} → {{ $b->check_out?->format('d/m/Y') }} · {{ ucfirst($b->channel) }}</div>
                        </div>
                        <div class="text-sm font-semibold">€{{ number_format($b->total_amount, 0, ',', '.') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-white/50">Ancora nessuna prenotazione.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
