<?php

namespace App\Services\Payment;

use App\Models\Booking;
use Stripe\StripeClient;

class PaymentService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.stripe.secret'));
    }

    private function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout session for a booking and return its URL.
     */
    public function createCheckoutUrl(Booking $booking, string $successUrl, string $cancelUrl): string
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $booking->guest?->email,
            'client_reference_id' => $booking->reference,
            'metadata' => ['booking_id' => $booking->id, 'reference' => $booking->reference],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($booking->currency),
                    'unit_amount' => (int) round($booking->total_amount * 100),
                    'product_data' => [
                        'name' => $booking->property?->title ?? 'Soggiorno HostUp',
                        'description' => sprintf(
                            '%s → %s · %d notti · %d ospiti',
                            $booking->check_in->format('d/m/Y'),
                            $booking->check_out->format('d/m/Y'),
                            $booking->nights,
                            $booking->guests_count,
                        ),
                    ],
                ],
            ]],
        ]);

        $booking->update(['payment_intent_id' => $session->id]);

        return $session->url;
    }

    /**
     * Was a checkout session actually paid?
     */
    public function sessionIsPaid(string $sessionId): bool
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId);

        return ($session->payment_status ?? null) === 'paid';
    }

    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret'),
        );
    }
}
