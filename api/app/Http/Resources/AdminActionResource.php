<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AdminAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdminAction */
class AdminActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action->value,
            'admin_id' => $this->admin_id,
            'target_user_id' => $this->target_user_id,
            'metadata' => $this->metadata,
            'admin' => $this->whenLoaded('admin', fn () => $this->admin ? new UserResource($this->admin) : null),
            'target' => $this->whenLoaded('target', fn () => $this->target ? new UserResource($this->target) : null),
            'created_at' => human_date($this->created_at),
        ];
    }
}
