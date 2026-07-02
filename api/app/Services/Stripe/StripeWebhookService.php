<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\User;
use App\Services\Finance\FinancialJournalService;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Charge;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Transfer;

class StripeWebhookService
{
    public function __construct(
        private NotificationService $notifications,
        private FinancialJournalService $journal
    ) {}

    public function handleEvent(Event $event): void
    {
        match ($event->type) {
            'payment_intent.succeeded' => $this->onPaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->onPaymentIntentFailed($event),
            'payment_intent.processing' => $this->onPaymentIntentProcessing($event),
            'payment_intent.amount_capturable_updated' => $this->onPaymentIntentAuthorized($event),
            'payment_intent.canceled' => $this->onPaymentIntentCanceled($event),
            'account.updated' => $this->onAccountUpdated($event),
            'charge.refunded' => $this->onChargeRefunded($event),
            'transfer.created' => $this->onTransferCreated($event),
            'transfer.failed' => $this->onTransferFailed($event),
            default => null,
        };
    }

    private function onAccountUpdated(Event $event): void
    {
        $object = $event->data->object ?? null;
        if (! $object instanceof Account) {
            return;
        }

        $onboardingCompleted = $object->details_submitted && $object->charges_enabled;

        $newlyOnboardedUsers = $onboardingCompleted
            ? User::where('stripe_account_id', $object->id)
                ->where('stripe_onboarding_completed', false)
                ->get()
            : new EloquentCollection;

        User::where('stripe_account_id', $object->id)
            ->update([
                'stripe_charges_enabled' => $object->charges_enabled,
                'stripe_payouts_enabled' => $object->payouts_enabled,
                'stripe_onboarding_completed' => $onboardingCompleted,
            ]);

        Activity::where('stripe_account_id', $object->id)
            ->update([
                'stripe_charges_enabled' => $object->charges_enabled,
                'stripe_payouts_enabled' => $object->payouts_enabled,
                'stripe_onboarding_completed' => $onboardingCompleted,
            ]);

        foreach ($newlyOnboardedUsers as $manager) {
            $this->notifications->notify(
                $manager,
                NotificationTypeEnum::STRIPE_ACCOUNT_ACTIVATED,
                ['stripe_account_id' => $object->id],
            );
        }
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

            if (! in_array($booking->status, [BookingStatusEnum::PENDING, BookingStatusEnum::CONFIRMED], true)) {
                Log::info('Stripe webhook: ignoring payment_intent.succeeded for booking in terminal state', [
                    'booking_id' => $booking->id,
                    'status' => $booking->status,
                ]);

                return;
            }

            $updates = [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => PaymentStatusEnum::SUCCEEDED,
                'paid_at' => Carbon::now(),
            ];

            if ($booking->stripe_charge_id === null && $paymentIntent->latest_charge !== null) {
                $updates['stripe_charge_id'] = $paymentIntent->latest_charge;
            }

            $booking->update($updates);

            $this->journal->record(
                FinancialOperationTypeEnum::CAPTURE,
                $booking,
                (string) $booking->total_price,
                $paymentIntent->latest_charge ?? $paymentIntent->id,
                ['source' => 'webhook'],
            );

            $this->notifyBookingUser($booking, NotificationTypeEnum::PAYMENT_SUCCEEDED);
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
                'payment_status' => PaymentStatusEnum::FAILED,
            ]);

            $this->notifyBookingUser($booking, NotificationTypeEnum::PAYMENT_FAILED);
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
                'payment_status' => PaymentStatusEnum::PROCESSING,
            ]);

            $this->notifyBookingUser($booking, NotificationTypeEnum::PAYMENT_PROCESSING);
        });
    }

    private function onPaymentIntentAuthorized(Event $event): void
    {
        $paymentIntent = $this->extractPaymentIntent($event);
        if ($paymentIntent === null) {
            return;
        }

        DB::transaction(function () use ($paymentIntent): void {
            $booking = $this->findBookingForPaymentIntent($paymentIntent);
            if ($booking === null || $booking->status !== BookingStatusEnum::PENDING) {
                return;
            }

            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => PaymentStatusEnum::REQUIRES_CAPTURE,
            ]);
        });
    }

    private function onPaymentIntentCanceled(Event $event): void
    {
        $paymentIntent = $this->extractPaymentIntent($event);
        if ($paymentIntent === null) {
            return;
        }

        DB::transaction(function () use ($paymentIntent): void {
            $booking = $this->findBookingForPaymentIntent($paymentIntent);
            if ($booking === null || $booking->payment_status === PaymentStatusEnum::REFUNDED) {
                return;
            }

            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => PaymentStatusEnum::CANCELED,
            ]);

            $this->journal->record(
                FinancialOperationTypeEnum::RELEASE,
                $booking,
                null,
                $paymentIntent->id,
                ['source' => 'webhook'],
            );
        });
    }

    private function onChargeRefunded(Event $event): void
    {
        $charge = $event->data->object ?? null;
        if ($charge === null) {
            return;
        }

        DB::transaction(function () use ($charge): void {
            $booking = Booking::where('stripe_charge_id', $charge->id)
                ->lockForUpdate()
                ->first();

            if ($booking === null) {
                Log::warning('Stripe webhook: no booking for charge.refunded', [
                    'charge_id' => $charge->id,
                ]);

                return;
            }

            $refundId = $charge->refunds->data[0]->id ?? null;
            $amountRefunded = $charge instanceof Charge ? (int) $charge->amount_refunded : 0;

            $booking->update([
                'stripe_refund_id' => $refundId,
                'refunded_amount' => bcdiv((string) $amountRefunded, '100', 2),
                'refunded_at' => Carbon::now(),
                'payment_status' => PaymentStatusEnum::REFUNDED,
            ]);

            $this->journal->record(
                FinancialOperationTypeEnum::REFUND,
                $booking,
                bcdiv((string) $amountRefunded, '100', 2),
                $refundId,
                ['source' => 'webhook'],
            );

            $this->notifyBookingUser($booking, NotificationTypeEnum::PAYMENT_REFUNDED);
        });
    }

    private function onTransferCreated(Event $event): void
    {
        $transfer = $event->data->object ?? null;
        if ($transfer === null) {
            return;
        }

        $bookingId = $transfer->metadata->booking_id ?? null;
        if ($bookingId === null) {
            return;
        }

        DB::transaction(function () use ($transfer, $bookingId): void {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->first();
            if ($booking === null) {
                return;
            }

            if ($booking->stripe_transfer_id !== null) {
                return;
            }

            $booking->update([
                'stripe_transfer_id' => $transfer->id,
            ]);

            $this->journal->record(
                FinancialOperationTypeEnum::PAYOUT,
                $booking,
                (string) $booking->activity_amount,
                $transfer->id,
                ['source' => 'webhook'],
            );
        });
    }

    private function onTransferFailed(Event $event): void
    {
        $transfer = $event->data->object ?? null;
        if ($transfer === null) {
            return;
        }

        $metadata = $transfer instanceof Transfer ? $transfer->metadata->toArray() : [];

        Log::warning('Stripe webhook: transfer.failed', [
            'transfer_id' => $transfer->id,
            'metadata' => $metadata,
        ]);
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

    private function notifyBookingUser(Booking $booking, NotificationTypeEnum $type): void
    {
        $booking->loadMissing('user');

        if ($booking->user === null) {
            return;
        }

        $this->notifications->notify(
            $booking->user,
            $type,
            [
                'booking_id' => $booking->id,
                'activity_id' => $booking->activity_id,
                'amount' => $booking->total_price,
            ],
        );
    }
}
