<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\BookingDispute;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BookingDisputeClosed implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BookingDispute $dispute) {}
}
