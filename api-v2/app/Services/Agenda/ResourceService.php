<?php

declare(strict_types=1);

namespace App\Services\Agenda;

use App\Enums\ResourceBookingKindEnum;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ressources de l'entreprise et leurs plannings dans chaque activité.
 */
class ResourceService
{
    /**
     * Relations affichées avec une ressource.
     */
    public const array RELATIONS = ['schedules', 'member.user'];

    /**
     * @return Collection<int, AgendaResource>
     */
    public function forOrganization(Organization $organization): Collection
    {
        return $organization->resources()->with(self::RELATIONS)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): AgendaResource
    {
        $resource = $organization->resources()->create($data);

        // Rechargée pour lire les valeurs par défaut posées par la base.
        return $resource->refresh()->load(self::RELATIONS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AgendaResource $resource, array $data): AgendaResource
    {
        $resource->update($data);

        return $resource->load(self::RELATIONS);
    }

    /**
     * Une ressource qui a déjà servi reste dans l'historique : on la désactive plutôt.
     */
    public function delete(AgendaResource $resource): void
    {
        if ($resource->resourceBookings()->where('kind', ResourceBookingKindEnum::BOOKING)->exists()) {
            throw ValidationException::withMessages(['resource' => __('agenda.resource_in_use')]);
        }

        DB::transaction(function () use ($resource): void {
            $resource->resourceBookings()->delete();
            $resource->delete();
        });
    }

    /**
     * Remplace d'un bloc la semaine de la ressource dans l'activité. Une liste vide la retire de l'activité.
     *
     * @param  list<array{weekday: int, start_time: string, end_time: string}>  $schedules
     */
    public function replaceSchedules(AgendaResource $resource, Activity $activity, array $schedules): AgendaResource
    {
        DB::transaction(function () use ($resource, $activity, $schedules): void {
            $resource->schedules()->where('activity_id', $activity->id)->delete();
            $resource->schedules()->createMany(array_map(fn (array $schedule): array => [
                ...$schedule,
                'organization_id' => $resource->organization_id,
                'activity_id' => $activity->id,
            ], $schedules));
        });

        return $resource->load(self::RELATIONS);
    }

    /**
     * Un membre quitte l'équipe. Sa ressource disparaît avec lui si elle n'a jamais servi ; sinon elle reste,
     * désactivée et sans lien, pour l'historique de ses rendez-vous. Ses rendez-vous à venir doivent d'abord
     * être annulés.
     */
    public function detachMember(OrganizationMember $member): void
    {
        $resource = $member->agendaResource;

        if ($resource === null) {
            return;
        }

        $bookings = $resource->resourceBookings()->where('kind', ResourceBookingKindEnum::BOOKING);

        if ($bookings->clone()->where('ends_at', '>', now())->exists()) {
            throw ValidationException::withMessages(['member' => __('agenda.member_has_appointments')]);
        }

        if ($bookings->exists()) {
            $resource->forceFill(['organization_member_id' => null, 'is_active' => false])->save();

            return;
        }

        $resource->resourceBookings()->delete();
        $resource->delete();
    }
}
