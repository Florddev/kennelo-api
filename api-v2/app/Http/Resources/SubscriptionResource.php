<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan' => SubscriptionPlanResource::make($this->whenLoaded('plan')),
            'status' => $this->status->value,
            'is_effective' => $this->isEffective(),
            'current_period_start' => $this->current_period_start?->toISOString(),
            'current_period_end' => $this->current_period_end?->toISOString(),
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'canceled_at' => $this->canceled_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
