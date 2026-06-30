<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Models\Booking;
use App\Models\BookingPayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

class BookingPayoutService
{
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function releaseDuePayouts(): int
    {
        $threshold = Carbon::now()->subHours((int) config('booking.payout_delay_hours', 24));

        $bookingIds = Booking::where('status', BookingStatusEnum::CONFIRMED)
            ->where('payment_status', PaymentStatusEnum::SUCCEEDED)
            ->whereNotNull('stripe_charge_id')
            ->where('activity_amount', '>', 0)
            ->where('check_in_date', '<=', $threshold)
            ->whereDoesntHave('payout')
            ->pluck('id');

        $count = 0;

        foreach ($bookingIds as $bookingId) {
            DB::transaction(function () use ($bookingId, &$count): void {
                $booking = Booking::with('activity.manager')
                    ->where('id', $bookingId)
                    ->lockForUpdate()
                    ->first();

                if ($booking === null || $booking->payout()->exists()) {
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
                    'currency' => strtoupper($currency),
                    'status' => PayoutStatusEnum::IN_TRANSIT,
                    'transferred_at' => Carbon::now(),
                ]);

                $booking->update(['stripe_transfer_id' => $transfer->id]);

                $count++;
            });
        }

        return $count;
    }
}
