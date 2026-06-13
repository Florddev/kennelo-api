<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Notification */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'data' => $this->data,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at ? human_date($this->read_at) : null,
            'created_at' => human_date($this->created_at),
        ];
    }
}
