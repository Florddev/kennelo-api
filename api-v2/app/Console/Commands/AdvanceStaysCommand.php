<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Booking\BookingLifecycleService;
use Illuminate\Console\Command;

/**
 * Tâche quotidienne : les séjours du jour commencent, ceux dont le départ est passé se terminent.
 */
class AdvanceStaysCommand extends Command
{
    protected $signature = 'bookings:advance-stays';

    protected $description = 'Start the stays of the day and complete the finished ones';

    public function handle(BookingLifecycleService $lifecycle): int
    {
        ['started' => $started, 'completed' => $completed] = $lifecycle->advanceStays();

        $this->info("Stays started: {$started}. Stays completed: {$completed}.");

        return self::SUCCESS;
    }
}
