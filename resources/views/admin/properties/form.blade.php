@extends('admin.layout')

@section('title', $property->exists ? 'Modifica immobile' : 'Nuovo immobile')

@php
    $field = 'mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan';
    $lbl = 'block text-xs font-semibold uppercase tracking-wide text-white/60';
@endphp

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.properties.index') }}" class="text-sm text-white/60 hover:text-white">← Immobili</a>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">{{ $property->exists ? $property->title : 'Nuovo immobile' }}</h1>
        </div>
        @if ($property->exists)
            <a href="{{ route('admin.availability.index', $property) }}" class="rounded-xl bg-white/10 px-4 py-2.5 text-sm hover:bg-white/15">Calendario & prezzi</a>
        @endif
    </div>

    <form method="post" action="{{ $property->exists ? route('admin.properties.update', $property) : route('admin.properties.store') }}" class="mt-6 grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($property->exists) @method('PUT') @endif

        {{-- Main column --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="glass rounded-2xl p-6 space-y-4">
                <h2 class="font-bold">Informazioni</h2>
                <div>
                    <label class="{{ $lbl }}">Titolo *</label>
                    <input name="title" value="{{ old('title', $property->title) }}" required class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Sottotitolo</label>
                    <input name="subtitle" value="{{ old('subtitle', $property->subtitle) }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Descrizione</label>
                    <textarea name="description" rows="7" class="{{ $field }}">{{ old('description', $property->description) }}</textarea>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 space-y-4">
                <h2 class="font-bold">Media & SEO</h2>
                <div>
                    <label class="{{ $lbl }}">URL video tour <span class="normal-case text-white/40">(YouTube, Vimeo o link .mp4)</span></label>
                    <input name="video_url" value="{{ old('video_url', $property->video_url) }}" placeholder="https://youtu.be/..." class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Titolo SEO <span class="normal-case text-white/40">(vuoto = automatico con città)</span></label>
                    <input name="meta_title" value="{{ old('meta_title', $property->meta_title) }}" maxlength="255" class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Descrizione SEO <span class="normal-case text-white/40">(max ~160 caratteri)</span></label>
                    <textarea name="meta_description" rows="2" maxlength="320" class="{{ $field }}">{{ old('meta_description', $property->meta_description) }}</textarea>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 space-y-4">
                <h2 class="font-bold">Posizione</h2>
                <div>
                    <label class="{{ $lbl }}">Indirizzo</label>
                    <input name="address" value="{{ old('address', $property->address) }}" class="{{ $field }}">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="{{ $lbl }}">Città</label><input name="city" value="{{ old('city', $property->city) }}" class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Regione</label><input name="region" value="{{ old('region', $property->region) }}" class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Paese</label><input name="country" maxlength="2" value="{{ old('country', $property->country) }}" class="{{ $field }}"></div>
                </div>
                <div>
                    <label class="{{ $lbl }}">Descrizione della zona <span class="normal-case text-white/40">(spiaggia, attrazioni, distanze — mostrata in pagina e utile alla SEO locale)</span></label>
                    <textarea name="area_description" rows="4" class="{{ $field }}">{{ old('area_description', $property->area_description) }}</textarea>
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <h2 class="font-bold">Servizi</h2>
                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($amenities as $amenity)
                        <label class="flex items-center gap-2 rounded-lg bg-white/5 px-3 py-2 text-sm">
                            <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                   @checked(in_array($amenity->id, old('amenities', $selectedAmenities)))
                                   class="rounded border-white/20 bg-white/10 text-cyan focus:ring-cyan">
                            <span>{{ $amenity->icon }} {{ $amenity->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar column --}}
        <div class="space-y-6">
            <div class="glass rounded-2xl p-6 space-y-4">
                <h2 class="font-bold">Pubblicazione</h2>
                <div>
                    <label class="{{ $lbl }}">Stato</label>
                    <select name="status" class="{{ $field }}">
                        <option value="draft" @selected(old('status', $property->status) === 'draft')>Bozza</option>
                        <option value="published" @selected(old('status', $property->status) === 'published')>Pubblicato</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $lbl }}">Tipo</label>
                        <select name="type" class="{{ $field }}">
                            @foreach (['apartment' => 'Appartamento', 'villa' => 'Villa', 'loft' => 'Loft', 'room' => 'Camera', 'house' => 'Casa'] as $val => $name)
                                <option value="{{ $val }}" @selected(old('type', $property->type) === $val)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="{{ $lbl }}">Ordine</label><input type="number" name="sort_order" value="{{ old('sort_order', $property->sort_order) }}" class="{{ $field }}"></div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 space-y-4">
                <h2 class="font-bold">Capienza</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $lbl }}">Ospiti max *</label><input type="number" name="max_guests" value="{{ old('max_guests', $property->max_guests) }}" required class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Camere *</label><input type="number" name="bedrooms" value="{{ old('bedrooms', $property->bedrooms) }}" required class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Letti *</label><input type="number" name="beds" value="{{ old('beds', $property->beds) }}" required class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Bagni *</label><input type="number" name="bathrooms" value="{{ old('bathrooms', $property->bathrooms) }}" required class="{{ $field }}"></div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 space-y-4">
                <h2 class="font-bold">Prezzi & regole</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $lbl }}">Prezzo/notte € *</label><input type="number" step="0.01" name="base_price" value="{{ old('base_price', $property->base_price) }}" required class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Pulizia € *</label><input type="number" step="0.01" name="cleaning_fee" value="{{ old('cleaning_fee', $property->cleaning_fee) }}" required class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Min notti *</label><input type="number" name="min_nights" value="{{ old('min_nights', $property->min_nights) }}" required class="{{ $field }}"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $lbl }}">Check-in</label><input name="check_in_time" placeholder="16:00" value="{{ old('check_in_time', $property->check_in_time) }}" class="{{ $field }}"></div>
                    <div><label class="{{ $lbl }}">Check-out</label><input name="check_out_time" placeholder="10:00" value="{{ old('check_out_time', $property->check_out_time) }}" class="{{ $field }}"></div>
                </div>
            </div>

            <button type="submit" class="brand-gradient w-full rounded-xl py-3 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">
                {{ $property->exists ? 'Salva modifiche' : 'Crea immobile' }}
            </button>
        </div>
    </form>

    @if ($property->exists)
        @include('admin.properties.partials.photos', ['property' => $property])
        @include('admin.properties.partials.channels', ['property' => $property, 'channels' => $channels, 'links' => $links])
    @endif
@endsection
