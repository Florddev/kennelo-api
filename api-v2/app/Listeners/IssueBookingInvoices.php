<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Booking\BookingPaymentCaptured;
use App\Services\Billing\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Un paiement encaissé (initial ou complément) produit la facture de l'entreprise et celle des frais Kennelo.
 * L'émission est idempotente : une nouvelle tentative n'émet que ce qui manque.
 */
class IssueBookingInvoices implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(private readonly InvoiceService $invoices) {}

    public function handle(BookingPaymentCaptured $event): void
    {
        $this->invoices->invoicePayment($event->payment);
    }
}
