<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Une part des paiements est rendue au client : annulation ou option retirée. Les lignes sont dans booking_refunds.
 */
final class BookingRefunded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  numeric-string  $amount  montant remboursé, frais Kennelo compris
     */
    public function __construct(public readonly Booking $booking, public readonly string $amount) {}
}
