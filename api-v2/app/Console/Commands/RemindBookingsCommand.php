<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Booking\BookingLifecycleService;
use Illuminate\Console\Command;

/**
 * Rappelle une fois à l'équipe une demande qui attend sa réponse.
 */
class RemindBookingsCommand extends Command
{
    protected $signature = 'bookings:remind';

    protected $description = 'Remind the teams of the booking requests waiting for their answer';

    public function handle(BookingLifecycleService $lifecycle): int
    {
        $reminded = $lifecycle->remindPending();

        $this->info("Reminders sent: {$reminded}.");

        return self::SUCCESS;
    }
}
