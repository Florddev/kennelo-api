<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'avatar_url' => $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
                ?: $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR)
                ?: null,
            'is_id_verified' => $this->is_id_verified,
            'email_verified_at' => $this->email_verified_at
                ? human_date($this->email_verified_at)
                : null,
            'status' => $this->when(
                auth()->user()?->hasRole('admin'),
                fn () => $this->status->value
            ),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'address' => $this->whenLoaded('address', fn () => new AddressResource($this->address)),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
