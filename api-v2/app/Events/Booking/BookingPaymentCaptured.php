<?php

declare(strict_types=1);

namespace App\Events\Booking;

use App\Models\BookingPayment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Un paiement est encaissé : le paiement initial à l'acceptation, ou un complément.
 */
final class BookingPaymentCaptured implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BookingPayment $payment) {}
}
