@php
    $field = 'mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan';
    $lbl = 'block text-xs font-semibold uppercase tracking-wide text-white/60';
    $exportUrl = route('ical.export', ['token' => $property->ical_token]);
@endphp

<div class="glass mt-6 rounded-2xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold">Sincronizzazione canali (iCal)</h2>
            <p class="text-xs text-white/50">Anti doppio-booking con Airbnb e Booking. Importa ogni 15 minuti in automatico.</p>
        </div>
        <form method="post" action="{{ route('admin.channels.sync', $property) }}">
            @csrf
            <button class="rounded-xl bg-white/10 px-4 py-2 text-sm hover:bg-white/15">↻ Sincronizza ora</button>
        </form>
    </div>

    {{-- Export feed (paste into OTAs) --}}
    <div class="mt-5 rounded-xl bg-white/5 p-4">
        <label class="{{ $lbl }}">Il TUO link iCal (incollalo dentro Airbnb e Booking)</label>
        <div class="mt-2 flex gap-2">
            <input readonly value="{{ $exportUrl }}" class="{{ $field }} font-mono text-xs" onclick="this.select()">
            <button type="button" class="rounded-xl bg-white/10 px-3 text-sm hover:bg-white/15"
                    onclick="navigator.clipboard.writeText('{{ $exportUrl }}'); this.textContent='Copiato!';">Copia</button>
        </div>
    </div>

    {{-- Import URLs --}}
    <form method="post" action="{{ route('admin.channels.update', $property) }}" class="mt-5 space-y-4">
        @csrf
        @foreach ($channels as $channel)
            @php $link = $links[$channel->id] ?? null; @endphp
            <div>
                <label class="{{ $lbl }}">
                    Link iCal da {{ $channel->name }}
                    @if ($link?->last_synced_at)
                        <span class="ml-2 text-cyan normal-case">· ultima sync {{ $link->last_synced_at->diffForHumans() }}</span>
                    @endif
                </label>
                <input name="links[{{ $channel->id }}][ical_import_url]" value="{{ old("links.{$channel->id}.ical_import_url", $link?->ical_import_url) }}"
                       placeholder="https://..." class="{{ $field }} font-mono text-xs">
            </div>
        @endforeach
        <button class="brand-gradient rounded-xl px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">Salva canali</button>
    </form>
</div>
