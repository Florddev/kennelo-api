<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class AdminActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'status' => $this->status->value,
            'is_active' => $this->is_active,
            'is_professional' => $this->siret !== null,
            'siret' => $this->siret,
            'siren' => $this->siren,
            'ape_code' => $this->ape_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'company_verified_at' => human_date($this->company_verified_at),
            'company_verification_data' => $this->company_verification_data,
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => human_date($this->reviewed_at),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? new UserResource($this->manager) : null),
            'reviewed_by' => $this->whenLoaded('reviewedBy', fn () => $this->reviewedBy ? new UserResource($this->reviewedBy) : null),
            'address' => new AddressResource($this->whenLoaded('address')),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
