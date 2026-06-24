<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityRole;
use App\Models\ActivityRolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityRole */
class ActivityRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'name' => $this->name,
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions
                    ->map(fn (ActivityRolePermission $permission) => $permission->permission->value)
                    ->values()
                    ->all()
            ),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
