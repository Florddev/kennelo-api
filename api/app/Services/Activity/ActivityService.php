<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\PaginationEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\ActivityCycleSettingPrice;
use App\Models\Address;
use App\Models\User;
use App\Services\Subscription\PlanLimitService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ActivityService
{
    private const DEFAULT_MAX_CAPACITY = 10;

    private const DEFAULT_PRICE = 20;

    public function __construct(
        private readonly PlanLimitService $planLimits
    ) {}

    public function getActivePaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Activity::with(['address', 'media', 'manager.media', 'collaborators.media'])
            ->active()
            ->whereHas('manager', function ($q) {
                $q->where('stripe_charges_enabled', true);
            })
            ->when(isset($filters['search']), fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['city']), fn ($q) => $q->whereHas('address', fn ($q) => $q->where('city', $filters['city'])))
            ->when(isset($filters['sort_by']), fn ($q) => $q->orderBy($filters['sort_by'], $filters['sort_dir'] ?? 'asc'))
            ->paginate($perPage);
    }

    public function getUserActivities(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Activity::with(['address', 'media', 'manager.media', 'collaborators.media'])
            ->withIsFavorited($user)
            ->where(function ($query) use ($user): void {
                $query->where('manager_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->when(isset($filters['search']), fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['sort_by']), fn ($q) => $q->orderBy($filters['sort_by'], $filters['sort_dir'] ?? 'asc'))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(string $id, ?User $user = null): Activity
    {
        $query = Activity::with(['address', 'media', 'manager.media', 'collaborators.media'])
            ->withIsFavorited($user);

        if (! $this->canViewUnverifiedManager($id, $user)) {
            $query->whereManagerVerified();
        }

        return $query->findOrFail($id);
    }

    private function canViewUnverifiedManager(string $id, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return Activity::where('id', $id)
            ->where(function ($query) use ($user): void {
                $query->where('manager_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->exists();
    }

    public function create(User $user, array $data): Activity
    {
        $this->planLimits->assertCanCreateActivity($user);

        return DB::transaction(function () use ($user, $data) {
            $addressId = null;

            if (isset($data['address'])) {
                $address = Address::create($data['address']);
                $addressId = $address->id;
            }

            $activity = Activity::create([
                'name' => $data['name'],
                'type' => $data['type'] ?? null,
                'description' => $data['description'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'siret' => $data['siret'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'address_id' => $addressId,
                'manager_id' => $user->id,
                'is_active' => true,
            ]);

            if (! $user->hasRole('manager')) {
                $user->assignRole('manager');
            }

            if (! empty($data['animal_type_ids'])) {
                $this->seedDefaultCycle($activity, $data['animal_type_ids']);
            }

            return $activity->load(['address', 'manager', 'collaborators']);
        });
    }

    /**
     * @param  array<int, string>  $animalTypeIds
     */
    private function seedDefaultCycle(Activity $activity, array $animalTypeIds): void
    {
        $cycle = ActivityCycle::create([
            'activity_id' => $activity->id,
            'start_date' => null,
            'end_date' => null,
            'priority' => 0,
            'is_active' => true,
        ]);

        foreach (array_unique($animalTypeIds) as $animalTypeId) {
            $setting = ActivityCycleSetting::create([
                'activity_cycle_id' => $cycle->id,
                'animal_type_id' => $animalTypeId,
                'max_capacity' => self::DEFAULT_MAX_CAPACITY,
            ]);

            foreach (WeekDayEnum::cases() as $weekday) {
                ActivityCycleSettingPrice::create([
                    'activity_cycle_setting_id' => $setting->id,
                    'weekday' => $weekday->value,
                    'price' => self::DEFAULT_PRICE,
                ]);
            }
        }
    }

    public function update(Activity $activity, array $data): Activity
    {
        return DB::transaction(function () use ($activity, $data) {
            if (isset($data['address'])) {
                if ($activity->address_id) {
                    $activity->address->update($data['address']);
                } else {
                    $address = Address::create($data['address']);
                    $data['address_id'] = $address->id;
                }
            }

            $activity->update(collect($data)->except('address')->toArray());

            return $activity->fresh(['address', 'manager', 'collaborators']);
        });
    }

    public function delete(Activity $activity): void
    {
        DB::transaction(fn () => $activity->delete());
    }
}
