<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Availability\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::published()
            ->with('coverPhoto')
            ->orderBy('sort_order')
            ->get();

        return view('public.properties', compact('properties'));
    }

    public function show(Property $property)
    {
        abort_unless($property->status === 'published', 404);

        $property->load(['photos', 'amenities']);

        return view('public.property', compact('property'));
    }

    public function quote(Request $request, Property $property, AvailabilityService $availability)
    {
        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1'],
        ]);

        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();

        if (($data['guests'] ?? 1) > $property->max_guests) {
            return response()->json([
                'ok' => false,
                'error' => "Numero ospiti superiore al massimo ({$property->max_guests}).",
            ], 422);
        }

        $quote = $availability->quote($property, $checkIn, $checkOut);

        if (! $quote) {
            return response()->json([
                'ok' => false,
                'error' => "Date non disponibili o soggiorno minimo di {$property->min_nights} notti non raggiunto.",
            ], 422);
        }

        return response()->json(['ok' => true, 'quote' => $quote]);
    }
}
