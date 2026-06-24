<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityRole;
use Illuminate\Support\Facades\DB;

class ActivityRoleService
{
    public function create(Activity $activity, array $data): ActivityRole
    {
        return DB::transaction(function () use ($activity, $data): ActivityRole {
            /** @var ActivityRole $role */
            $role = $activity->roles()->create(['name' => $data['name']]);
            $this->syncPermissions($role, $data['permissions']);

            return $role->load('permissions');
        });
    }

    public function update(ActivityRole $role, array $data): ActivityRole
    {
        return DB::transaction(function () use ($role, $data): ActivityRole {
            $role->update(['name' => $data['name']]);
            $this->syncPermissions($role, $data['permissions']);

            return $role->load('permissions');
        });
    }

    public function delete(ActivityRole $role): void
    {
        DB::transaction(fn () => $role->delete());
    }

    private function syncPermissions(ActivityRole $role, array $permissions): void
    {
        $role->permissions()->delete();

        foreach (array_unique($permissions) as $permission) {
            $role->permissions()->create(['permission' => $permission]);
        }
    }
}
