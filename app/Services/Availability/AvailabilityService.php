<?php

namespace App\Services\Availability;

use App\Models\Availability;
use App\Models\Property;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class AvailabilityService
{
    /**
     * Ensure a property has an open availability row for every day in the
     * window [today, today+$days]. Existing days are left untouched, missing
     * ones are created as 'available' at the property's base price.
     * Used on property creation and to roll the booking window forward.
     *
     * @return int number of days created
     */
    public function ensureCalendar(Property $property, int $days = 540): int
    {
        $start = Carbon::today();
        $end = Carbon::today()->addDays($days);

        $existing = $property->availability()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $rows = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $key = $day->toDateString();
            if ($existing->has($key)) {
                continue;
            }
            $rows[] = [
                'property_id' => $property->id,
                'date' => $key,
                'status' => 'available',
                'price' => $property->base_price,
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            Availability::insert($chunk);
        }

        return count($rows);
    }

    /**
     * All nights in [checkIn, checkOut) — checkout day itself is not occupied.
     *
     * @return array<int, string> list of Y-m-d date strings
     */
    public function nights(Carbon $checkIn, Carbon $checkOut): array
    {
        $dates = [];
        $cursor = $checkIn->copy();
        while ($cursor->lt($checkOut)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }

    /**
     * Is the whole stay bookable? Every night must exist and be 'available'.
     */
    public function isRangeAvailable(Property $property, Carbon $checkIn, Carbon $checkOut): bool
    {
        $nights = $this->nights($checkIn, $checkOut);
        if (empty($nights)) {
            return false;
        }

        $openCount = Availability::where('property_id', $property->id)
            ->whereIn('date', $nights)
            ->where('status', 'available')
            ->count();

        return $openCount === count($nights);
    }

    /**
     * Build a price quote for a stay. Returns null if not bookable.
     *
     * @return array{nights:int, breakdown:array, accommodation:float, cleaning_fee:float, total:float, currency:string}|null
     */
    public function quote(Property $property, Carbon $checkIn, Carbon $checkOut): ?array
    {
        $nights = $this->nights($checkIn, $checkOut);
        $count = count($nights);

        if ($count === 0 || $count < (int) $property->min_nights) {
            return null;
        }

        $rows = Availability::where('property_id', $property->id)
            ->whereIn('date', $nights)
            ->where('status', 'available')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        if ($rows->count() !== $count) {
            return null; // some night missing or not available
        }

        $breakdown = [];
        $accommodation = 0.0;
        foreach ($nights as $date) {
            $price = (float) ($rows[$date]->price ?? $property->base_price);
            $accommodation += $price;
            $breakdown[] = ['date' => $date, 'price' => $price];
        }

        $cleaning = (float) $property->cleaning_fee;

        return [
            'nights' => $count,
            'breakdown' => $breakdown,
            'accommodation' => round($accommodation, 2),
            'cleaning_fee' => round($cleaning, 2),
            'total' => round($accommodation + $cleaning, 2),
            'currency' => 'EUR',
        ];
    }
}
