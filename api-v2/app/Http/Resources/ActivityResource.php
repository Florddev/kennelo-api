<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\LocationModeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Services\MediaService;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une activité, vue par un client, par son équipe ou par un admin.
 *
 * Le client ne voit l'adresse exacte que si l'activité reçoit chez elle : celle d'un pro qui se déplace
 * ou travaille à distance est souvent son domicile, il n'en voit que la ville.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $organization = $this->relationLoaded('organization') ? $this->organization : null;
        $permissions = app(OrganizationPermissions::class);
        $isTeamMember = $user !== null && $organization !== null && $permissions->isMember($user, $organization);
        $canSeePrivate = $isTeamMember || (bool) $user?->hasRole('admin');
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->id,
            'organization' => $this->whenLoaded('organization', fn (): array => [
                'id' => $this->organization?->id,
                'legal_name' => $this->organization?->legal_name,
                'legal_form' => $this->organization?->legal_form->value,
            ]),
            'profession' => ProfessionResource::make($this->whenLoaded('profession')),
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->whenLoaded('address', fn () => $canSeePrivate || $this->serves_at_pro
                ? AddressResource::make($this->address)
                : [
                    'postal_code' => $this->address?->postal_code,
                    'city' => $this->address?->city,
                    'department' => $this->address?->department,
                    'country' => $this->address?->country,
                ]),
            'establishment_siret' => $this->establishment_siret,
            'timezone' => $this->timezone,
            'locations' => array_map(fn (LocationModeEnum $location): string => $location->value, $this->locations()),
            'service_radius_km' => $this->service_radius_km,
            'cancellation_policy' => $this->cancellation_policy->value,
            'is_active' => $this->is_active,
            'animal_types' => AnimalTypeResource::collection($this->whenLoaded('animalTypes')),
            'opening_hours' => ActivityOpeningHourResource::collection($this->whenLoaded('openingHours')),
            'images' => $this->whenLoaded('media', fn () => ImageResource::collection(
                $canSeePrivate ? $this->getMedia(MediaService::COLLECTION_IMAGES) : $this->resource->publicImages(),
            )),
            'is_favorited' => $this->when(array_key_exists('is_favorited', $attributes), fn (): bool => (bool) $this->resource->getAttribute('is_favorited')),
            'distance_km' => $this->when(isset($attributes['distance']), fn (): float => round((float) $attributes['distance'], 1)),
            // Note moyenne des avis publiés des clients, sur 5 ; null sans avis.
            'rating' => $this->when(array_key_exists('rating_count', $attributes), fn (): array => [
                'average' => (int) $attributes['rating_count'] > 0 ? round((float) $attributes['rating_average'], 1) : null,
                'count' => (int) $attributes['rating_count'],
            ]),
            'status' => $this->when($canSeePrivate, fn (): string => $this->status->value),
            'rejection_reason' => $this->when($canSeePrivate, fn (): ?string => $this->rejection_reason),
            'reviewed_at' => $this->when($canSeePrivate, fn (): ?string => $this->reviewed_at?->toISOString()),
            // Un justificatif obligatoire manque ou a expiré : l'activité reste hors de la recherche tant qu'il n'est pas remplacé.
            'has_missing_documents' => $this->when(
                $canSeePrivate && array_key_exists('has_missing_documents', $attributes),
                fn (): bool => (bool) $this->resource->getAttribute('has_missing_documents'),
            ),
            // Droits de la personne connectée sur cette activité, pour afficher ou masquer les actions.
            'permissions' => $this->when($isTeamMember, fn (): array => array_map(
                fn (OrganizationPermissionEnum $permission): string => $permission->value,
                $organization === null || $user === null ? [] : $permissions->permissionsFor($user, $organization, $this->id),
            )),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
