<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * issuer vaut « kennelo » pour les frais de service et les récapitulatifs de commission, « organization » pour
 * les factures des réservations, émises par Kennelo au nom de l'entreprise. Les montants sont TTC, HT et TVA.
 *
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'type' => $this->type->value,
            'issuer' => $this->isIssuedByKennelo() ? 'kennelo' : 'organization',
            'issuer_organization_id' => $this->issuer_organization_id,
            'issuer_details' => $this->issuer_details,
            'recipient_user_id' => $this->recipient_user_id,
            'recipient_organization_id' => $this->recipient_organization_id,
            'recipient_details' => $this->recipient_details,
            'booking_id' => $this->booking_id,
            'credited_invoice' => $this->whenLoaded('creditedInvoice', fn () => $this->creditedInvoice === null ? null : [
                'id' => $this->creditedInvoice->id,
                'number' => $this->creditedInvoice->number,
            ]),
            'currency' => $this->currency,
            'total_ht' => $this->total_ht,
            'total_vat' => $this->total_vat,
            'total_ttc' => $this->total_ttc,
            'vat_mention' => $this->vat_mention,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'issued_at' => $this->issued_at->toISOString(),
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
