<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Enums\BookingStatusEnum;
use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Enums\OrganizationStatusEnum;
use App\Enums\VatRegimeEnum;
use App\Models\Address;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\Organization\Exceptions\OrganizationCannotBeClosedException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Une identité vérifiée qui change doit être vérifiée de nouveau.
     */
    private const array VERIFIED_IDENTITY_FIELDS = ['legal_name', 'legal_form', 'siren', 'siret'];

    /**
     * @return Collection<int, Organization>
     */
    public function forMember(User $user): Collection
    {
        return Organization::query()
            ->whereHas('members', fn ($query) => $query->active()->whereBelongsTo($user))
            ->with(['address', 'subscription.plan'])
            ->orderBy('legal_name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Crée l'entreprise et la ligne membre de son propriétaire : il accède aussitôt à l'espace de gestion.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Organization
    {
        return DB::transaction(function () use ($owner, $data): Organization {
            $organization = new Organization(Arr::except($data, 'address'));
            $organization->owner_id = $owner->id;

            if (isset($data['address'])) {
                $organization->address_id = Address::create($data['address'])->id;
            }

            $organization->save();

            $organization->members()->create([
                'user_id' => $owner->id,
                'status' => OrganizationMemberStatusEnum::ACTIVE,
                'responded_at' => now(),
            ]);

            // Rechargée pour lire les valeurs par défaut posées par la base (statut, régime de TVA…).
            return $organization->refresh()->load(['address', 'subscription.plan']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        DB::transaction(function () use ($organization, $data): void {
            if (isset($data['address'])) {
                $this->saveAddress($organization, $data['address']);
            }

            $organization->fill(Arr::except($data, 'address'));

            // Un particulier n'a ni SIREN ni TVA : on efface ce qui restait d'une ancienne forme juridique.
            if (! $organization->legal_form->hasSiren()) {
                $organization->fill([
                    'siren' => null,
                    'siret' => null,
                    'ape_code' => null,
                    'vat_number' => null,
                    'vat_regime' => VatRegimeEnum::FRANCHISE,
                ]);
            }

            if ($organization->status === OrganizationStatusEnum::VERIFIED && $organization->isDirty(self::VERIFIED_IDENTITY_FIELDS)) {
                $organization->forceFill([
                    'status' => OrganizationStatusEnum::PENDING,
                    'verified_at' => null,
                ]);
            }

            $organization->save();
        });

        return $organization->load(['address', 'subscription.plan']);
    }

    /**
     * Ferme l'entreprise. Ses membres perdent l'accès à l'espace de gestion ; l'historique est conservé.
     */
    public function close(Organization $organization): void
    {
        $subscription = $organization->subscription;

        if ($subscription !== null && $subscription->isEffective() && ! $subscription->isCanceling()) {
            throw OrganizationCannotBeClosedException::subscriptionStillActive();
        }

        // Requête directe : le modèle Booking arrive avec le lot « Réservation commune et séjours ».
        $hasActiveBookings = DB::table('bookings')
            ->where('organization_id', $organization->id)
            ->whereIn('status', [
                BookingStatusEnum::PENDING->value,
                BookingStatusEnum::CONFIRMED->value,
                BookingStatusEnum::IN_PROGRESS->value,
            ])
            ->exists();

        if ($hasActiveBookings) {
            throw OrganizationCannotBeClosedException::hasActiveBookings();
        }

        $organization->delete();
    }

    /**
     * Cède l'entreprise à un membre actif. L'ancien propriétaire reste dans l'équipe comme gérant ;
     * le nouveau n'a plus besoin de rôle puisqu'il a désormais tous les droits.
     */
    public function transferOwnership(Organization $organization, OrganizationMember $newOwner): Organization
    {
        DB::transaction(function () use ($organization, $newOwner): void {
            $previousOwnerMembership = $organization->members()->where('user_id', $organization->owner_id)->firstOrFail();

            $previousOwnerMembership->roles()->create([
                'organization_id' => $organization->id,
                'role' => OrganizationRoleEnum::MANAGER,
            ]);

            $newOwner->roles()->delete();

            $organization->update(['owner_id' => $newOwner->user_id]);
        });

        return $organization->load(['address', 'subscription.plan']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function saveAddress(Organization $organization, array $attributes): void
    {
        if ($organization->address !== null) {
            $organization->address->update($attributes);

            return;
        }

        $organization->address_id = Address::create($attributes)->id;
    }
}
