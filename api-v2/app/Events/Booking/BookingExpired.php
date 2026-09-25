<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sans réponse du pro à temps : l'autorisation de paiement est libérée.
 */
final class BookingExpired implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
