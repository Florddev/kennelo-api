<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserAddress */
class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'is_default' => $this->is_default,
            'address' => AddressResource::make($this->whenLoaded('address')),
        ];
    }
}
