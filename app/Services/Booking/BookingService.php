<?php

namespace App\Services\Booking;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Property;
use App\Services\Availability\AvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * Create a direct booking and HOLD the nights atomically.
     *
     * The availability rows for the stay are locked (SELECT ... FOR UPDATE) so two
     * concurrent checkouts cannot grab the same dates. The booking is created as
     * 'pending' and the nights are marked 'booked' immediately.
     *
     * @param  array{name:string, email:string, phone?:string}  $guestData
     *
     * @throws BookingException
     */
    public function create(Property $property, Carbon $checkIn, Carbon $checkOut, int $guestsCount, array $guestData, string $channel = 'direct'): Booking
    {
        $nights = $this->availability->nights($checkIn, $checkOut);
        $nightsCount = count($nights);

        if ($nightsCount === 0) {
            throw new BookingException('Date non valide.');
        }
        if ($nightsCount < (int) $property->min_nights) {
            throw new BookingException("Soggiorno minimo di {$property->min_nights} notti.");
        }
        if ($guestsCount > (int) $property->max_guests) {
            throw new BookingException("Massimo {$property->max_guests} ospiti.");
        }

        return DB::transaction(function () use ($property, $checkIn, $checkOut, $nights, $nightsCount, $guestsCount, $guestData, $channel) {
            // Lock the nights so nobody else can take them concurrently.
            $rows = Availability::where('property_id', $property->id)
                ->whereIn('date', $nights)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

            // Every night must exist and be free.
            $accommodation = 0.0;
            foreach ($nights as $date) {
                $row = $rows[$date] ?? null;
                if (! $row || $row->status !== 'available') {
                    throw new BookingException('Le date selezionate non sono più disponibili.');
                }
                $accommodation += (float) ($row->price ?? $property->base_price);
            }

            $total = round($accommodation + (float) $property->cleaning_fee, 2);

            $guest = Guest::firstOrCreate(
                ['email' => $guestData['email']],
                ['name' => $guestData['name'], 'phone' => $guestData['phone'] ?? null],
            );
            // Keep latest name/phone
            $guest->fill([
                'name' => $guestData['name'],
                'phone' => $guestData['phone'] ?? $guest->phone,
            ])->save();

            $booking = Booking::create([
                'property_id' => $property->id,
                'guest_id' => $guest->id,
                'channel' => $channel,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'nights' => $nightsCount,
                'guests_count' => $guestsCount,
                'total_amount' => $total,
                'currency' => 'EUR',
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            // Hold the nights.
            Availability::where('property_id', $property->id)
                ->whereIn('date', $nights)
                ->update([
                    'status' => 'booked',
                    'booking_id' => $booking->id,
                    'source' => $channel,
                    'updated_at' => now(),
                ]);

            return $booking;
        });
    }

    /**
     * Confirm a booking after successful payment.
     */
    public function markPaid(Booking $booking): void
    {
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Release a held booking and free its nights.
     */
    public function release(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            Availability::where('booking_id', $booking->id)->update([
                'status' => 'available',
                'booking_id' => null,
                'source' => 'manual',
                'updated_at' => now(),
            ]);

            $booking->update(['status' => 'cancelled']);
        });
    }
}
