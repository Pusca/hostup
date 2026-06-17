<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, PaymentService $payments, BookingService $bookings)
    {
        if (! $payments->isConfigured() || ! config('services.stripe.webhook_secret')) {
            return response()->json(['ok' => false], 400);
        }

        try {
            $event = $payments->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature failed: ' . $e->getMessage());

            return response()->json(['ok' => false], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $bookingId = $session->metadata->booking_id ?? null;

            if ($bookingId && ($booking = Booking::find($bookingId))) {
                if (($session->payment_status ?? null) === 'paid') {
                    $bookings->markPaid($booking);
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
