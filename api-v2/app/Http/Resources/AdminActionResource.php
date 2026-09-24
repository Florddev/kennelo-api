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
            'admin' => $this->whenLoaded('admin', fn () => $this->admin ? [
                'id' => $this->admin->id,
                'first_name' => $this->admin->first_name,
                'last_name' => $this->admin->last_name,
                'email' => $this->admin->email,
            ] : null),
            'target' => $this->whenLoaded('target', fn () => $this->target ? [
                'id' => $this->target->id,
                'first_name' => $this->target->first_name,
                'last_name' => $this->target->last_name,
                'email' => $this->target->email,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
