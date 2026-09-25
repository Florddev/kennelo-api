<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrganizationMember;
use App\Models\OrganizationMemberRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationMember */
class OrganizationMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Une invitation ne montre que le nom de l'entreprise : son adresse peut être celle d'un particulier.
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'legal_name' => $this->organization->legal_name,
            ]),
            'user' => UserResource::make($this->whenLoaded('user')),
            'status' => $this->status->value,
            'is_owner' => $this->whenLoaded('organization', fn () => $this->isOwner()),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn (OrganizationMemberRole $role): array => [
                'role' => $role->role->value,
                'activity_id' => $role->activity_id,
            ])),
            // Sa fiche dans l'agenda : à proposer quand il réalise des prestations et n'en a pas encore.
            'resource_id' => $this->whenLoaded('agendaResource', fn (): ?string => $this->agendaResource?->id),
            'invited_at' => $this->invited_at?->toISOString(),
            'responded_at' => $this->responded_at?->toISOString(),
        ];
    }
}
