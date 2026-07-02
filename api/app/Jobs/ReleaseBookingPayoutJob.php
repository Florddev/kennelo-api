<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Booking\BookingPayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReleaseBookingPayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $bookingId) {}

    public function handle(BookingPayoutService $service): void
    {
        $service->releasePayout($this->bookingId);
    }
}
