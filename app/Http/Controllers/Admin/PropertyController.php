<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Property;
use App\Services\Availability\AvailabilityService;
use App\Services\Geo\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::withCount('photos')->orderBy('sort_order')->get();

        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $property = new Property([
            'status' => 'draft',
            'type' => 'apartment',
            'country' => 'IT',
            'max_guests' => 2,
            'bedrooms' => 1,
            'beds' => 1,
            'bathrooms' => 1,
            'min_nights' => 1,
            'base_price' => 0,
            'cleaning_fee' => 0,
        ]);
        $amenities = Amenity::orderBy('name')->get();

        return view('admin.properties.form', [
            'property' => $property,
            'amenities' => $amenities,
            'selectedAmenities' => [],
        ]);
    }

    public function store(Request $request, AvailabilityService $availability, GeocodingService $geo)
    {
        $data = $this->validateData($request);

        $property = Property::create($data);
        $property->amenities()->sync($request->input('amenities', []));

        // Generate the booking calendar so quotes/bookings work right away.
        $availability->ensureCalendar($property);

        $this->geocodeIfNeeded($property, $geo);

        return redirect()->route('admin.properties.edit', $property)
            ->with('status', 'Immobile creato (calendario generato). Ora aggiungi le foto.');
    }

    public function edit(Property $property)
    {
        $property->load('photos', 'amenities', 'channelLinks');
        $amenities = Amenity::orderBy('name')->get();
        $channels = \App\Models\Channel::where('code', '!=', 'direct')->where('is_active', true)->get();
        $links = $property->channelLinks->keyBy('channel_id');

        return view('admin.properties.form', [
            'property' => $property,
            'amenities' => $amenities,
            'selectedAmenities' => $property->amenities->pluck('id')->all(),
            'channels' => $channels,
            'links' => $links,
        ]);
    }

    public function update(Request $request, Property $property, GeocodingService $geo)
    {
        $data = $this->validateData($request, $property);

        $property->update($data);
        $property->amenities()->sync($request->input('amenities', []));

        $this->geocodeIfNeeded($property, $geo);

        return back()->with('status', 'Modifiche salvate.');
    }

    /**
     * Popola lat/lng dall'indirizzo (OpenStreetMap) quando mancano o l'indirizzo è cambiato.
     */
    private function geocodeIfNeeded(Property $property, GeocodingService $geo): void
    {
        $needs = empty($property->lat) || empty($property->lng)
            || $property->wasChanged(['address', 'city', 'region', 'country']);

        if (! $needs) {
            return;
        }

        $coords = $geo->geocodeAddress($property->address, $property->city, $property->region, $property->country);

        if ($coords) {
            $property->forceFill($coords)->saveQuietly();
        }
    }

    public function destroy(Property $property)
    {
        // Remove stored photo files for this property
        Storage::disk('public')->deleteDirectory('properties/' . $property->slug);
        $property->delete();

        return redirect()->route('admin.properties.index')->with('status', 'Immobile eliminato.');
    }

    private function validateData(Request $request, ?Property $property = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:draft,published'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'area_description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:2'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:50'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:30'],
            'beds' => ['required', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:30'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cleaning_fee' => ['required', 'numeric', 'min:0'],
            'min_nights' => ['required', 'integer', 'min:1', 'max:60'],
            'check_in_time' => ['nullable', 'string', 'max:10'],
            'check_out_time' => ['nullable', 'string', 'max:10'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Generate a unique slug from title on create (kept stable afterwards)
        if (! $property) {
            $base = Str::slug($data['title']) ?: Str::random(8);
            $slug = $base;
            $i = 1;
            while (Property::where('slug', $slug)->exists()) {
                $slug = $base . '-' . (++$i);
            }
            $data['slug'] = $slug;
        }

        return $data;
    }
}
