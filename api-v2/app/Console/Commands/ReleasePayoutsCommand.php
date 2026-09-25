<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Booking\BookingPayoutService;
use Illuminate\Console\Command;

/**
 * Verse aux entreprises ce que leurs réservations terminées leur rapportent.
 */
class ReleasePayoutsCommand extends Command
{
    protected $signature = 'bookings:release-payouts';

    protected $description = 'Pay the organizations what their finished bookings earned them';

    public function handle(BookingPayoutService $payouts): int
    {
        $released = $payouts->releaseDue();

        $this->info("Payouts released: {$released}.");

        return self::SUCCESS;
    }
}
