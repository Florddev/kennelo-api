<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Booking\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $bookingId) {}

    public function handle(BookingService $service): void
    {
        $service->remindBooking($this->bookingId);
    }
}
