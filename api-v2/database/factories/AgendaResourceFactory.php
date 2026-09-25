<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResourceTypeEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaResource>
 */
class AgendaResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'type' => ResourceTypeEnum::EQUIPMENT,
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }

    /**
     * La fiche d'un membre de l'équipe.
     */
    public function staff(OrganizationMember $member): static
    {
        return $this->state(fn (): array => [
            'organization_id' => $member->organization_id,
            'type' => ResourceTypeEnum::STAFF,
            'organization_member_id' => $member->id,
        ]);
    }

    /**
     * Planifiée dans l'activité, de $from à $to, chaque jour de la semaine.
     */
    public function scheduledIn(Activity $activity, string $from = '09:00', string $to = '18:00'): static
    {
        return $this->state(fn (): array => ['organization_id' => $activity->organization_id])
            ->afterCreating(fn (AgendaResource $resource) => $resource->schedules()->createMany(array_map(fn (WeekDayEnum $day): array => [
                'organization_id' => $resource->organization_id,
                'activity_id' => $activity->id,
                'weekday' => $day,
                'start_time' => $from,
                'end_time' => $to,
            ], WeekDayEnum::cases())));
    }
}
