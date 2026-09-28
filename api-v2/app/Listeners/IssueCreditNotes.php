<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Booking\BookingRefunded;
use App\Services\Billing\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Un remboursement produit ses avoirs : chez l'entreprise pour les prestations, chez Kennelo pour sa part des
 * frais. Tous les remboursements de la réservation sans avoir sont traités, ce qui rattrape un échec précédent.
 */
class IssueCreditNotes implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(private readonly InvoiceService $invoices) {}

    public function handle(BookingRefunded $event): void
    {
        $this->invoices->creditRefunds($event->booking);
    }
}
