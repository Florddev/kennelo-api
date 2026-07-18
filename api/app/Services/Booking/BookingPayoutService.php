<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\ActivityPermissionEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Jobs\ReleaseBookingPayoutJob;
use App\Models\Booking;
use App\Models\BookingPayout;
use App\Services\Finance\FinancialJournalService;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class BookingPayoutService
{
    public function __construct(
        private StripeClient $stripe,
        private FinancialJournalService $journal,
        private NotificationService $notifications,
        private NotificationRecipientResolver $recipients
    ) {}

    public function releaseDuePayouts(): int
    {
        $threshold = now()->subHours((int) setting('payout_delay_hours', config('booking.payout_delay_hours', 24)));

        $bookingIds = Booking::where('status', BookingStatusEnum::CONFIRMED)
            ->where('payment_status', PaymentStatusEnum::SUCCEEDED)
            ->whereNotNull('stripe_charge_id')
            ->where('activity_amount', '>', 0)
            ->where('check_in_date', '<=', $threshold)
            ->whereDoesntHave('payout')
            ->pluck('id');

        foreach ($bookingIds as $bookingId) {
            ReleaseBookingPayoutJob::dispatch((string) $bookingId);
        }

        return $bookingIds->count();
    }

    public function releasePayout(string $bookingId): void
    {
        DB::transaction(function () use ($bookingId): void {
            $booking = Booking::with('activity.manager')
                ->where('id', $bookingId)
                ->lockForUpdate()
                ->first();

            if ($booking === null || $booking->payout()->exists()) {
                return;
            }

            if ($booking->status !== BookingStatusEnum::CONFIRMED
                || $booking->payment_status !== PaymentStatusEnum::SUCCEEDED
                || $booking->stripe_charge_id === null
                || (float) $booking->activity_amount <= 0) {
                return;
            }

            $accountId = $booking->activity?->resolveStripeAccountId();

            if ($accountId === null || $booking->activity->resolvePayoutsEnabled() !== true) {
                return;
            }

            $currency = (string) config('services.stripe.currency', 'eur');

            $transfer = $this->stripe->transfers->create([
                'amount' => (int) bcmul((string) $booking->activity_amount, '100', 0),
                'currency' => $currency,
                'destination' => $accountId,
                'source_transaction' => $booking->stripe_charge_id,
                'transfer_group' => $booking->stripe_transfer_group ?? ('booking_'.$booking->id),
                'metadata' => ['booking_id' => $booking->id],
            ], ['idempotency_key' => 'payout_'.$booking->id]);

            BookingPayout::create([
                'booking_id' => $booking->id,
                'stripe_transfer_id' => $transfer->id,
                'activity_stripe_account_id' => $accountId,
                'amount' => $booking->activity_amount,
                'currency' => Str::upper($currency),
                'status' => PayoutStatusEnum::IN_TRANSIT,
                'transferred_at' => now(),
            ]);

            $booking->update(['stripe_transfer_id' => $transfer->id]);

            $this->journal->record(
                FinancialOperationTypeEnum::PAYOUT,
                $booking,
                (string) $booking->activity_amount,
                $transfer->id,
            );

            $this->notifications->notify(
                $this->recipients->forActivity($booking->activity, ActivityPermissionEnum::MANAGE_BOOKINGS),
                NotificationTypeEnum::PAYOUT_SENT,
                [
                    'booking_id' => $booking->id,
                    'activity_id' => $booking->activity_id,
                    'activity_name' => $booking->activity->name,
                    'amount' => $booking->activity_amount,
                ],
            );
        });
    }
}
