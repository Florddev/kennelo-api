<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\LocationModeEnum;
use App\Models\Activity;
use App\Models\Address;
use App\Models\Organization;
use App\Services\Activity\Exceptions\ActivityCannotBeDeletedException;
use App\Services\Subscription\PlanLimitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ActivityService
{
    /**
     * Relations affichées avec une activité. L'offre de l'entreprise décide des photos montrées au public.
     */
    public const array RELATIONS = ['organization.subscription.plan', 'profession.category', 'address', 'animalTypes', 'openingHours', 'media'];

    public function __construct(
        private readonly PlanLimitService $planLimits,
    ) {}

    /**
     * @return Collection<int, Activity>
     */
    public function forOrganization(Organization $organization): Collection
    {
        return $organization->activities()
            ->with(self::RELATIONS)
            ->withExists(Activity::missingDocumentsCheck())
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * L'activité est créée en attente : elle ne devient réservable qu'une fois approuvée par Kennelo.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): Activity
    {
        return DB::transaction(function () use ($organization, $data): Activity {
            // Verrou sur l'entreprise : deux créations simultanées ne dépassent pas le quota à elles deux.
            Organization::query()->whereKey($organization->id)->lockForUpdate()->first();
            $this->planLimits->assertCanAddActivity($organization);

            $activity = new Activity($this->attributes($data));
            $activity->organization_id = $organization->id;
            $activity->timezone = $data['timezone'] ?? config('activities.default_timezone');

            if (isset($data['address'])) {
                $activity->address_id = Address::create($data['address'])->id;
            }

            $activity->save();
            $activity->animalTypes()->sync($data['animal_type_ids']);

            // Rechargée pour lire les valeurs par défaut posées par la base (statut, politique d'annulation…).
            return $activity->refresh()->load(self::RELATIONS);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Activity $activity, array $data): Activity
    {
        DB::transaction(function () use ($activity, $data): void {
            if (! $activity->is_active && (bool) ($data['is_active'] ?? false)) {
                Organization::query()->whereKey($activity->organization_id)->lockForUpdate()->first();
                $this->planLimits->assertCanReopenActivity($activity);
            }

            if (isset($data['address'])) {
                $this->saveAddress($activity, $data['address']);
            }

            $activity->fill($this->attributes($data))->save();

            if (isset($data['animal_type_ids'])) {
                $activity->animalTypes()->sync($data['animal_type_ids']);
            }
        });

        return $activity->load(self::RELATIONS);
    }

    /**
     * Suppression logique : l'historique des réservations reste lisible.
     */
    public function delete(Activity $activity): void
    {
        if ($activity->bookings()->occupying()->exists()) {
            throw new ActivityCannotBeDeletedException;
        }

        $activity->delete();
    }

    /**
     * Les lieux arrivent sous forme de liste ; la table les range en trois booléens.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = Arr::except($data, ['address', 'animal_type_ids', 'locations']);

        if (isset($data['locations'])) {
            foreach (LocationModeEnum::cases() as $location) {
                $attributes['serves_'.$location->value] = in_array($location->value, $data['locations'], true);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function saveAddress(Activity $activity, array $attributes): void
    {
        if ($activity->address !== null) {
            $activity->address->update($attributes);

            return;
        }

        $activity->address_id = Address::create($attributes)->id;
    }
}
