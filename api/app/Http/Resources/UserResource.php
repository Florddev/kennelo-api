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
        $isSelf = $request->user()?->id === $this->id;
        $isAdmin = auth()->user()?->hasRole('admin');

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
            'two_factor_enabled' => $isSelf ? ($this->two_factor_confirmed_at !== null) : false,
            'two_factor_recovery_codes_count' => $isSelf && $this->two_factor_confirmed_at !== null
                ? count($this->two_factor_recovery_codes ?? [])
                : 0,
            'has_password' => $isSelf ? $this->password !== null : null,
            'status' => $this->when($isAdmin, fn () => $this->status->value),
            'is_banned' => $this->when($isAdmin, fn () => $this->isBanned()),
            'ban_reason' => $this->when($isAdmin, fn () => $this->ban_reason),
            'banned_at' => $this->when($isAdmin, fn () => $this->banned_at ? human_date($this->banned_at) : null),
            'banned_until' => $this->when($isAdmin, fn () => $this->banned_until ? human_date($this->banned_until) : null),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'address' => $this->whenLoaded('address', fn () => new AddressResource($this->address)),
            'stripe_account_id' => $isSelf ? $this->stripe_account_id : null,
            'stripe_customer_id' => $isSelf ? $this->stripe_customer_id : null,
            'stripe_charges_enabled' => $isSelf ? (bool) $this->stripe_charges_enabled : false,
            'stripe_payouts_enabled' => $isSelf ? (bool) $this->stripe_payouts_enabled : false,
            'stripe_onboarding_completed' => $isSelf ? (bool) $this->stripe_onboarding_completed : false,
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
