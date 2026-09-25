<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Booking\BookingLifecycleService;
use Illuminate\Console\Command;

/**
 * Les réservations confirmées commencent et se terminent : les séjours au jour près, les rendez-vous à l'heure près.
 */
class AdvanceBookingsCommand extends Command
{
    protected $signature = 'bookings:advance';

    protected $description = 'Start the bookings that have begun and complete the finished ones';

    public function handle(BookingLifecycleService $lifecycle): int
    {
        ['started' => $started, 'completed' => $completed] = $lifecycle->advance();

        $this->info("Bookings started: {$started}. Bookings completed: {$completed}.");

        return self::SUCCESS;
    }
}
