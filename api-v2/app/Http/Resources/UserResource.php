<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
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
        $isAdmin = $request->user()?->hasRole('admin');
        $canViewContact = $isSelf || $isAdmin;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->when($canViewContact, fn () => $this->email),
            'phone' => $this->when($canViewContact, fn () => $this->phone),
            'locale' => $this->locale,
            'avatar_url' => $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
                ?: $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR)
                ?: null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'two_factor_enabled' => $isSelf ? ($this->two_factor_confirmed_at !== null) : false,
            'two_factor_recovery_codes_count' => $isSelf && $this->two_factor_confirmed_at !== null
                ? count($this->two_factor_recovery_codes ?? [])
                : 0,
            'has_password' => $isSelf ? $this->password !== null : null,
            'can_access_management' => $isSelf ? $this->canAccessManagement() : null,
            'status' => $this->when($isAdmin, fn (): UserStatusEnum => $this->status),
            'is_banned' => $this->when($isAdmin, fn () => $this->isBanned()),
            'ban_reason' => $this->when($isAdmin, fn () => $this->ban_reason),
            'banned_at' => $this->when($isAdmin, fn () => $this->banned_at?->toISOString()),
            'banned_until' => $this->when($isAdmin, fn () => $this->banned_until?->toISOString()),
            /** @var list<RoleEnum> */
            'roles' => $this->whenLoaded('roles', fn (): array => $this->getRoleNames()->map(fn (string $role): RoleEnum => RoleEnum::from($role))->values()->all()),
            'stripe_customer_id' => $isSelf ? $this->stripe_customer_id : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
