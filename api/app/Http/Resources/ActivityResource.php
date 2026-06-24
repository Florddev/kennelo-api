<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;
use App\Models\ActivityCycleSetting;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = collect();

        if ($this->relationLoaded('cycles')) {
            foreach ($this->cycles as $cycle) {
                $settings = $settings->merge($cycle->settings);
            }
        }

        $minPrice = $settings->isNotEmpty()
            ? (float) $settings->min('price')
            : null;

        $animalTypes = $settings->isNotEmpty()
            ? $settings->map(fn (ActivityCycleSetting $setting) => $setting->animalType->code)->filter()->unique()->values()->all()
            : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'siret' => $this->siret,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address_id' => $this->address_id,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'manager_id' => $this->manager_id,
            'is_favorited' => $this->when(
                array_key_exists('is_favorited', $this->resource->getAttributes()),
                fn (): bool => (bool) $this->resource->getAttribute('is_favorited')
            ),
            'is_professional' => $this->siret !== null,
            'type' => $this->resource->getRawOriginal('type'),
            'min_price' => $minPrice,
            'animal_types' => $animalTypes,
            'avatar_url' => $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
                ?: $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR)
                ?: null,
            'stripe_account_id' => $this->resolveStripeAccountId(),
            'stripe_onboarding_completed' => (bool) $this->stripe_onboarding_completed,
            'stripe_charges_enabled' => $this->resolveChargesEnabled(),
            'stripe_payouts_enabled' => $this->resolvePayoutsEnabled(),
            'address' => new AddressResource($this->whenLoaded('address')),
            'manager' => new UserResource($this->whenLoaded('manager')),
            'collaborators' => UserResource::collection($this->whenLoaded('collaborators')),
            'images' => ActivityImageResource::collection($this->getMedia(MediaService::COLLECTION_IMAGES)),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
