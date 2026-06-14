<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityCollaborator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityCollaborator */
class ActivityCollaboratorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'activity_id' => $this->activity_id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'user' => new UserResource($this->whenLoaded('user')),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'role' => $this->role !== null ? new ActivityRoleResource($this->role) : null,
            'invited_at' => $this->invited_at !== null ? human_date($this->invited_at) : null,
            'responded_at' => $this->responded_at !== null ? human_date($this->responded_at) : null,
        ];
    }
}
