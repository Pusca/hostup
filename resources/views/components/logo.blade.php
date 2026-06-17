@props([
    'size' => 18,        // wordmark font-size in px
    'badge' => 44,       // badge square in px
])

<span {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="brand-gradient grid place-items-center rounded-[14px] shadow-lg ring-1 ring-white/20"
          style="width: {{ $badge }}px; height: {{ $badge }}px;" aria-hidden="true" title="HostUp">
        <svg viewBox="0 0 24 24" class="fill-white opacity-95" style="width: {{ round($badge * 0.5) }}px; height: {{ round($badge * 0.5) }}px;">
            <path d="M6.5 5.5c0-.55.45-1 1-1h2c.55 0 1 .45 1 1v4.2l3.25-3.25c.2-.2.46-.3.73-.3H17c.55 0 1 .45 1 1v11c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1V11.7l-3.25 3.25c-.2.2-.46.3-.73.3H7.5c-.55 0-1-.45-1-1v-8.8Z"/>
        </svg>
    </span>
    <span class="font-extrabold tracking-tight leading-none" style="font-size: {{ $size }}px;">
        <span class="text-white/90">Host</span><span class="text-gradient font-black">Up</span>
    </span>
</span>
