@props([
    'size' => 18,        // wordmark font-size in px
    'badge' => 44,       // badge square in px
])

<span {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    {{-- Badge: icona ufficiale HU (freccia verso l'alto) --}}
    <img src="{{ asset('brand/hu-badge.png') }}" alt="HostUp"
         class="shadow-lg" style="width: {{ $badge }}px; height: {{ $badge }}px;">
    <span class="font-black tracking-tight leading-none" style="font-size: {{ $size }}px;">
        <span class="text-white">Host</span><span style="color:#3f87f5;">Up</span>
    </span>
</span>
