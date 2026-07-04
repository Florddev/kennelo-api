<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\PaginationEnum;
use App\Models\Activity;
use App\Models\Address;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ActivityService
{
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

            return $activity->load(['address', 'manager', 'collaborators']);
        });
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
