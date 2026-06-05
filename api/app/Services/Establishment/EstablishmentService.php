<?php

declare(strict_types=1);

namespace App\Services\Establishment;

use App\Enums\PaginationEnum;
use App\Models\Address;
use App\Models\Establishment;
use App\Models\EstablishmentCollaboratorPermission;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EstablishmentService
{
    public function getActivePaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Establishment::with(['address', 'manager', 'collaborators'])
            ->active()
            ->whereHas('manager', function ($q) {
                $q->where('stripe_charges_enabled', true);
            })
            ->when(isset($filters['search']), fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['city']), fn ($q) => $q->whereHas('address', fn ($q) => $q->where('city', $filters['city'])))
            ->when(isset($filters['sort_by']), fn ($q) => $q->orderBy($filters['sort_by'], $filters['sort_dir'] ?? 'asc'))
            ->paginate($perPage);
    }

    public function getUserEstablishments(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Establishment::with(['address', 'manager', 'collaborators'])
            ->where('manager_id', $user->id)
            ->when(isset($filters['search']), fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['sort_by']), fn ($q) => $q->orderBy($filters['sort_by'], $filters['sort_dir'] ?? 'asc'))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(string $id): Establishment
    {
        return Establishment::with(['address', 'manager', 'collaborators'])->findOrFail($id);
    }

    public function create(User $user, array $data): Establishment
    {
        return DB::transaction(function () use ($user, $data) {
            $addressId = null;

            if (isset($data['address'])) {
                $address = Address::create($data['address']);
                $addressId = $address->id;
            }

            $establishment = Establishment::create([
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

            return $establishment->load(['address', 'manager', 'collaborators']);
        });
    }

    public function update(Establishment $establishment, array $data): Establishment
    {
        return DB::transaction(function () use ($establishment, $data) {
            if (isset($data['address'])) {
                if ($establishment->address_id) {
                    $establishment->address->update($data['address']);
                } else {
                    $address = Address::create($data['address']);
                    $data['address_id'] = $address->id;
                }
            }

            $establishment->update(collect($data)->except('address')->toArray());

            return $establishment->fresh(['address', 'manager', 'collaborators']);
        });
    }

    public function delete(Establishment $establishment): void
    {
        DB::transaction(fn () => $establishment->delete());
    }

    public function syncCollaboratorPermissions(Establishment $establishment, User $collaborator, array $permissions): void
    {
        DB::transaction(function () use ($establishment, $collaborator, $permissions): void {
            EstablishmentCollaboratorPermission::where('establishment_id', $establishment->id)
                ->where('user_id', $collaborator->id)
                ->delete();

            foreach ($permissions as $permission) {
                EstablishmentCollaboratorPermission::create([
                    'establishment_id' => $establishment->id,
                    'user_id' => $collaborator->id,
                    'permission' => $permission,
                ]);
            }
        });
    }
}
