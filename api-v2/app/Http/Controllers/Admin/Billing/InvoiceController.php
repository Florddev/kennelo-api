<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Billing\ListInvoicesRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin Billing
 */
class InvoiceController extends Controller
{
    /**
     * List all invoices
     *
     * Toutes les factures et tous les avoirs, de Kennelo comme ceux émis au nom des entreprises.
     */
    public function index(ListInvoicesRequest $request, InvoiceService $invoices): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        return InvoiceResource::collection($invoices->forAdmin($request->validated()));
    }
}
