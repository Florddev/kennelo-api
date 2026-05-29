<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Establishment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Event;
use Stripe\PaymentIntent;

class StripeWebhookService
{
    public function handleEvent(Event $event): void
    {
        match ($event->type) {
            'payment_intent.succeeded' => $this->onPaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->onPaymentIntentFailed($event),
            'payment_intent.processing' => $this->onPaymentIntentProcessing($event),
            'account.updated' => $this->onAccountUpdated($event),
            default => null,
        };
    }

    private function onAccountUpdated(Event $event): void
    {
        $object = $event->data->object ?? null;
        if (! $object instanceof Account) {
            return;
        }

        Establishment::where('stripe_account_id', $object->id)
            ->update([
                'stripe_charges_enabled' => $object->charges_enabled,
                'stripe_payouts_enabled' => $object->payouts_enabled,
                'stripe_onboarding_completed' => $object->details_submitted,
            ]);
    }

    private function onPaymentIntentSucceeded(Event $event): void
    {
        $paymentIntent = $this->extractPaymentIntent($event);
        if ($paymentIntent === null) {
            return;
        }

        DB::transaction(function () use ($paymentIntent): void {
            $booking = $this->findBookingForPaymentIntent($paymentIntent);

            if ($booking === null) {
                Log::warning('Stripe webhook: no booking for payment_intent', [
                    'payment_intent_id' => $paymentIntent->id,
                    'metadata' => $paymentIntent->metadata->toArray(),
                ]);

                return;
            }

            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => PaymentStatus::Succeeded,
                'paid_at' => Carbon::now(),
                'status' => BookingStatus::CONFIRMED,
            ]);
        });
    }

    private function onPaymentIntentFailed(Event $event): void
    {
        $paymentIntent = $this->extractPaymentIntent($event);
        if ($paymentIntent === null) {
            return;
        }

        DB::transaction(function () use ($paymentIntent): void {
            $booking = $this->findBookingForPaymentIntent($paymentIntent);
            if ($booking === null) {
                return;
            }

            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => PaymentStatus::Failed,
            ]);
        });
    }

    private function onPaymentIntentProcessing(Event $event): void
    {
        $paymentIntent = $this->extractPaymentIntent($event);
        if ($paymentIntent === null) {
            return;
        }

        DB::transaction(function () use ($paymentIntent): void {
            $booking = $this->findBookingForPaymentIntent($paymentIntent);
            if ($booking === null) {
                return;
            }

            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => PaymentStatus::Processing,
            ]);
        });
    }

    private function findBookingForPaymentIntent(PaymentIntent $paymentIntent): ?Booking
    {
        $bookingId = $paymentIntent->metadata->booking_id ?? null;

        if ($bookingId !== null) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->first();
            if ($booking !== null) {
                return $booking;
            }
        }

        return Booking::where('stripe_payment_intent_id', $paymentIntent->id)
            ->lockForUpdate()
            ->first();
    }

    private function extractPaymentIntent(Event $event): ?PaymentIntent
    {
        $object = $event->data->object ?? null;

        return $object instanceof PaymentIntent ? $object : null;
    }
}
