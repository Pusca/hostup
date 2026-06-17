@php
    $cover = $property->coverPhoto ?? $property->photos->first();
    $img = $cover?->url ?? 'https://picsum.photos/seed/hostup-fallback/800/600';
@endphp

<a href="{{ route('properties.show', $property) }}"
   class="group block overflow-hidden rounded-2xl glass transition hover:-translate-y-1 hover:ring-cyan/40 hover:shadow-2xl">
    <div class="relative aspect-[4/3] overflow-hidden">
        <img src="{{ $img }}" alt="{{ $property->title }}"
             class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
        <div class="absolute left-3 top-3 rounded-full bg-navy-950/70 px-3 py-1 text-xs font-semibold text-white backdrop-blur ring-1 ring-white/15">
            {{ $property->city }}
        </div>
    </div>
    <div class="p-5">
        <h3 class="text-xl font-bold leading-snug">{{ $property->title }}</h3>
        <p class="mt-1 text-sm text-white/60">{{ $property->subtitle }}</p>
        <div class="mt-4 flex items-center gap-4 text-sm text-white/70">
            <span>👤 {{ $property->max_guests }} ospiti</span>
            <span>🛏 {{ $property->bedrooms }} camere</span>
            <span>🛁 {{ $property->bathrooms }}</span>
        </div>
        <div class="mt-4 flex items-baseline justify-between border-t border-white/10 pt-4">
            <span class="text-white/60 text-sm">da</span>
            <span><span class="text-2xl font-extrabold">€{{ number_format($property->base_price, 0, ',', '.') }}</span> <span class="text-sm text-white/60">/ notte</span></span>
        </div>
    </div>
</a>
