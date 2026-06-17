<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Services\Availability\AvailabilityService;
use App\Services\Booking\BookingException;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CheckoutController extends Controller
{
    public function show(Request $request, Property $property, AvailabilityService $availability)
    {
        abort_unless($property->status === 'published', 404);

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1'],
        ]);

        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();
        $guests = (int) ($data['guests'] ?? 1);

        $quote = $availability->quote($property, $checkIn, $checkOut);
        if (! $quote) {
            return redirect()->route('properties.show', $property)
                ->with('error', 'Le date selezionate non sono disponibili.');
        }

        return view('public.checkout', compact('property', 'checkIn', 'checkOut', 'guests', 'quote'));
    }

    public function store(Request $request, Property $property, BookingService $bookings, PaymentService $payments)
    {
        abort_unless($property->status === 'published', 404);

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();

        try {
            $booking = $bookings->create($property, $checkIn, $checkOut, (int) $data['guests'], [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);
        } catch (BookingException $e) {
            return redirect()->route('properties.show', $property)->with('error', $e->getMessage());
        }

        if (! empty($data['note'])) {
            $booking->update(['notes' => $data['note']]);
        }

        // Real payment if Stripe is configured, otherwise demo confirmation.
        if ($payments->isConfigured()) {
            try {
                $url = $payments->createCheckoutUrl(
                    $booking,
                    route('checkout.success', $booking) . '?session_id={CHECKOUT_SESSION_ID}',
                    route('checkout.cancel', $booking),
                );

                return redirect()->away($url);
            } catch (\Throwable $e) {
                $bookings->release($booking);

                return redirect()->route('properties.show', $property)
                    ->with('error', 'Errore nell\'avvio del pagamento. Riprova.');
            }
        }

        // DEMO mode (no Stripe keys): confirm immediately, flagged as simulated.
        $bookings->markPaid($booking);
        $booking->update(['notes' => trim(($booking->notes ? $booking->notes . ' · ' : '') . 'Pagamento SIMULATO (Stripe non configurato)')]);

        return redirect()->route('checkout.success', $booking);
    }

    public function success(Request $request, Booking $booking, PaymentService $payments, BookingService $bookings)
    {
        // If a real Stripe session is present, verify it was actually paid.
        if ($payments->isConfigured() && $request->filled('session_id')) {
            if ($payments->sessionIsPaid($request->string('session_id'))) {
                $bookings->markPaid($booking);
            }
        }

        $booking->load('property', 'guest');

        return view('public.confirmation', compact('booking'));
    }

    public function cancel(Booking $booking, BookingService $bookings)
    {
        if ($booking->status === 'pending') {
            $bookings->release($booking);
        }

        return redirect()->route('properties.show', $booking->property)
            ->with('error', 'Prenotazione annullata. Le date sono di nuovo disponibili.');
    }
}
