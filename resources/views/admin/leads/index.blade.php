@extends('admin.layout')

@section('title', 'Richieste proprietari')

@section('content')
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Richieste proprietari</h1>
            <p class="mt-1 text-white/60">Lead arrivati dal form "valutazione gratuita" del sito.</p>
        </div>
    </div>

    <div class="mt-8 space-y-4">
        @forelse ($leads as $lead)
            <div class="glass rounded-2xl p-6 {{ $lead->status === 'new' ? 'ring-1 ring-cyan/40' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-bold">{{ $lead->name }}</h2>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                {{ $lead->status === 'new' ? 'bg-cyan/15 text-cyan' : ($lead->status === 'contacted' ? 'bg-amber-400/15 text-amber-300' : 'bg-white/10 text-white/50') }}">
                                {{ $lead->statusLabel() }}
                            </span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-white/70">
                            <a href="mailto:{{ $lead->email }}" class="hover:text-white">✉ {{ $lead->email }}</a>
                            @if ($lead->phone)
                                <a href="tel:{{ $lead->phone }}" class="hover:text-white">📞 {{ $lead->phone }}</a>
                            @endif
                            @if ($lead->city)
                                <span>📍 {{ $lead->city }}</span>
                            @endif
                            @if ($lead->property_type)
                                <span>🏠 {{ $lead->property_type }}</span>
                            @endif
                        </div>
                        @if ($lead->message)
                            <p class="mt-3 max-w-3xl text-sm text-white/65">{{ $lead->message }}</p>
                        @endif
                        <p class="mt-3 text-xs text-white/40">Ricevuta il {{ $lead->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="post" action="{{ route('admin.leads.update', $lead) }}">
                            @csrf
                            @method('patch')
                            <select name="status" onchange="this.form.submit()"
                                    class="rounded-lg border border-white/15 bg-navy-950 px-3 py-2 text-sm text-white [color-scheme:dark]">
                                @foreach (\App\Models\OwnerLead::STATUSES as $value => $label)
                                    <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="post" action="{{ route('admin.leads.destroy', $lead) }}"
                              onsubmit="return confirm('Eliminare questa richiesta?')">
                            @csrf
                            @method('delete')
                            <button class="rounded-lg border border-red-400/30 px-3 py-2 text-sm text-red-300 hover:bg-red-500/10">Elimina</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass rounded-2xl p-10 text-center text-white/60">
                Nessuna richiesta per ora. Quando un proprietario compila il form sul sito, la troverai qui.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $leads->links() }}
    </div>
@endsection
