<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Property;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    public function index(Property $property)
    {
        $from = Carbon::today();
        $to = Carbon::today()->addDays(120);

        $days = $property->availability()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($d) => Carbon::parse($d->date)->toDateString());

        return view('admin.properties.calendar', compact('property', 'days', 'from', 'to'));
    }

    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'status' => ['required', 'in:available,blocked'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $from = Carbon::parse($data['from']);
        $to = Carbon::parse($data['to']);

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $row = Availability::firstOrNew([
                'property_id' => $property->id,
                'date' => $day->toDateString(),
            ]);

            // Never silently override a booked night from here
            if ($row->status === 'booked') {
                continue;
            }

            $row->status = $data['status'];
            if (isset($data['price']) && $data['price'] !== null) {
                $row->price = $data['price'];
            }
            $row->source = 'manual';
            $row->save();
        }

        return back()->with('status', 'Calendario aggiornato.');
    }
}
