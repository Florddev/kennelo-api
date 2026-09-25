<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\BookingPayment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * La banque du client exige qu'il confirme un paiement complémentaire (3-D Secure).
 */
final class BookingPaymentActionRequired implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BookingPayment $payment) {}
}
