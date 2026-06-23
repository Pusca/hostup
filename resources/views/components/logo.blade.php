@props([
    'size' => 18,        // wordmark font-size in px
    'badge' => 44,       // badge square in px
])

<span {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="brand-gradient grid place-items-center rounded-[14px] shadow-lg ring-1 ring-white/20"
          style="width: {{ $badge }}px; height: {{ $badge }}px;" aria-hidden="true" title="HostUp">
        {{-- Casetta (Host) + freccia su (Up) --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="opacity-95" style="width: {{ round($badge * 0.52) }}px; height: {{ round($badge * 0.52) }}px;">
            <path d="M4 11 L12 4 L20 11 V19 a1 1 0 0 1 -1 1 H5 a1 1 0 0 1 -1 -1 Z"/>
            <path d="M12 18 V11"/>
            <path d="M9 13.5 L12 10.5 L15 13.5"/>
        </svg>
    </span>
    <span class="font-extrabold tracking-tight leading-none" style="font-size: {{ $size }}px;">
        <span class="text-white/90">Host</span><span class="text-gradient font-black">Up</span>
    </span>
</span>
