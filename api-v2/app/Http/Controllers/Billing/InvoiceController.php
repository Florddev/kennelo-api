<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ListInvoicesRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Billing\InvoicePdfRenderer;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @tags Billing
 */
class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * List my invoices
     *
     * Factures et avoirs reçus en tant que client : ceux des réservations, émis au nom des entreprises, et ceux
     * des frais de service de Kennelo. Les plus récents d'abord.
     */
    public function index(ListInvoicesRequest $request): AnonymousResourceCollection
    {
        return InvoiceResource::collection($this->invoices->forUser($request->user(), $request->validated()));
    }

    /**
     * Show an invoice
     *
     * Pour son client, pour l'équipe de l'entreprise qui l'a émise ou reçue (finance.view), et pour Kennelo.
     */
    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice->load(['lines', 'creditedInvoice']));
    }

    /**
     * Download an invoice as PDF
     *
     * Le PDF produit à l'émission, pour les mêmes personnes que la facture.
     */
    public function pdf(Invoice $invoice, InvoicePdfRenderer $pdfs): StreamedResponse
    {
        $this->authorize('view', $invoice);

        return $pdfs->download($invoice);
    }
}
