<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'brand' => $this['brand'],
            'last4' => $this['last4'],
            'exp_month' => (int) $this['exp_month'],
            'exp_year' => (int) $this['exp_year'],
            'is_default' => (bool) $this['is_default'],
            'cardholder_name' => $this['cardholder_name'] ?? null,
        ];
    }
}
