<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Stripe\Invoice;

/**
 * Facture d'abonnement émise par Stripe Billing.
 *
 * @mixin Invoice
 */
class SubscriptionInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'amount_paid' => bcdiv((string) ($this->amount_paid ?? 0), '100', 2),
            'amount_due' => bcdiv((string) ($this->amount_due ?? 0), '100', 2),
            'currency' => mb_strtoupper((string) $this->currency),
            'created_at' => $this->created ? Carbon::createFromTimestamp($this->created)->toISOString() : null,
            'invoice_pdf' => $this->invoice_pdf,
            'hosted_invoice_url' => $this->hosted_invoice_url,
        ];
    }
}
