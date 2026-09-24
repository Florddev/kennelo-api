<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\OrganizationPermissionEnum;
use App\Models\Organization;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isAdmin = (bool) $user?->hasRole('admin');

        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'legal_form' => $this->legal_form->value,
            'siren' => $this->siren,
            'siret' => $this->siret,
            'ape_code' => $this->ape_code,
            'vat_number' => $this->vat_number,
            'vat_regime' => $this->vat_regime->value,
            'address' => AddressResource::make($this->whenLoaded('address')),
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'verified_at' => $this->verified_at?->toISOString(),
            // Sans abonnement, la relation chargée vaut null : l'offre est alors la gratuite.
            'plan' => $this->when($this->relationLoaded('subscription'), fn () => $this->effectivePlan()->value),
            'stripe_charges_enabled' => $this->stripe_charges_enabled,
            'stripe_payouts_enabled' => $this->stripe_payouts_enabled,
            'stripe_onboarding_completed' => $this->stripe_onboarding_completed,
            'billing_mandate_accepted_at' => $this->billing_mandate_accepted_at?->toISOString(),
            // Droits de la personne connectée sur toute l'entreprise, pour afficher ou masquer les actions.
            'is_owner' => $user !== null && $this->isOwnedBy($user),
            'permissions' => $user === null ? [] : array_map(
                fn (OrganizationPermissionEnum $permission): string => $permission->value,
                app(OrganizationPermissions::class)->permissionsFor($user, $this->resource),
            ),
            'owner' => $this->when($isAdmin, fn () => UserResource::make($this->whenLoaded('owner'))),
            'verification_data' => $this->when($isAdmin, fn () => $this->verification_data),
            'reviewed_at' => $this->when($isAdmin, fn () => $this->reviewed_at?->toISOString()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
