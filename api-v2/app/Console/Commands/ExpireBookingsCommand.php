<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Booking\BookingLifecycleService;
use Illuminate\Console\Command;

/**
 * Demandes restées sans réponse : elles expirent et l'autorisation de paiement est libérée.
 */
class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire';

    protected $description = 'Expire the booking requests left without an answer and release their payment';

    public function handle(BookingLifecycleService $lifecycle): int
    {
        $expired = $lifecycle->expirePending();

        $this->info("Bookings expired: {$expired}.");

        return self::SUCCESS;
    }
}
