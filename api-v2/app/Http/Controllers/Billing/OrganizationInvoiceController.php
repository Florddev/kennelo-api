<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ListOrganizationInvoicesRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Organization;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Invoices
 */
class OrganizationInvoiceController extends Controller
{
    /**
     * List the organization's invoices
     *
     * Émises (factures et avoirs de ses réservations, au nom de l'entreprise) et reçues (récapitulatifs mensuels
     * de commission de Kennelo). Droit finance.view. Les factures d'abonnement restent celles de Stripe
     * (/subscription/invoices).
     */
    public function index(ListOrganizationInvoicesRequest $request, Organization $organization, InvoiceService $invoices): AnonymousResourceCollection
    {
        $this->authorize('viewFinance', $organization);

        return InvoiceResource::collection($invoices->forOrganization($organization, $request->validated()));
    }
}
