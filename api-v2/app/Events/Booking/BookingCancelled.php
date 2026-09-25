<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Annulée par le client, le pro ou Kennelo (bookings.cancelled_by_role).
 */
final class BookingCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
