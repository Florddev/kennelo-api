<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FinancialOperation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FinancialOperation
 */
class FinancialOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'stripe_reference' => $this->stripe_reference,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
