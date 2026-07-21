@props([
    'size' => 18,        // wordmark font-size in px
    'badge' => 44,       // badge square in px
])

@php $uid = 'hu' . uniqid(); @endphp

<span {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    {{-- Badge: monogramma HU con freccia verso l'alto --}}
    <svg viewBox="0 0 48 48" class="shadow-lg rounded-[13px]" aria-hidden="true"
         style="width: {{ $badge }}px; height: {{ $badge }}px;">
        <defs>
            <linearGradient id="{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#57a2f7"/>
                <stop offset="1" stop-color="#2b66e8"/>
            </linearGradient>
        </defs>
        <rect width="48" height="48" rx="13" fill="url(#{{ $uid }})"/>
        <g fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
            {{-- H (l'asta destra sale e diventa freccia = Up) --}}
            <path d="M11 17 V34"/>
            <path d="M11 25 H19"/>
            <path d="M19 34 V12"/>
            <path d="M14.8 15.8 L19 11.6 L23.2 15.8"/>
            {{-- U --}}
            <path d="M28 17 V27.5 a4.5 4.5 0 0 0 9 0 V17"/>
        </g>
    </svg>
    <span class="font-black tracking-tight leading-none" style="font-size: {{ $size }}px;">
        <span class="text-white">Host</span><span style="color:#3f87f5;">Up</span>
    </span>
</span>
