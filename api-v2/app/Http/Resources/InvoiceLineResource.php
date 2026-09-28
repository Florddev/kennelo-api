<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceLine
 */
class InvoiceLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_ttc' => $this->unit_price_ttc,
            'vat_rate' => $this->vat_rate,
            'total_ht' => $this->total_ht,
            'total_vat' => $this->total_vat,
            'total_ttc' => $this->total_ttc,
        ];
    }
}
