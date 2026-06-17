@extends('layouts.public')

@section('title', 'Immobili — HostUp')

@section('content')
    <section class="border-b border-white/10 pt-32 pb-16">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan">La collezione</p>
            <h1 class="mt-2 text-5xl font-extrabold tracking-tight">Tutti gli immobili</h1>
            <p class="mt-3 max-w-xl text-white/60">{{ $properties->count() }} case selezionate, pronte ad accoglierti.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 sm:px-8 py-16">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($properties as $property)
                @include('public.partials.property-card', ['property' => $property])
            @endforeach
        </div>
    </section>
@endsection
