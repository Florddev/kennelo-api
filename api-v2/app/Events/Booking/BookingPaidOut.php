<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\BookingPayout;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Le montant dû à l'entreprise est versé sur son compte Stripe.
 */
final class BookingPaidOut implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BookingPayout $payout) {}
}
