<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\Billing\InvoicePdfRenderer;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Produit le PDF d'une facture qui vient d'être émise, une fois la transaction d'émission validée : une émission
 * annulée ne laisse pas de PDF orphelin.
 */
class GenerateInvoicePdf implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly Invoice $invoice) {}

    public function handle(InvoicePdfRenderer $pdfs): void
    {
        $pdfs->store($this->invoice);
    }
}
