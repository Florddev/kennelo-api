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
            'activity_id' => $this->activity_id,
            'plan' => $this->plan?->slug,
            'status' => $this->status->value,
            'is_effective' => $this->isEffective(),
            'current_period_start' => $this->current_period_start ? human_date($this->current_period_start) : null,
            'current_period_end' => $this->current_period_end ? human_date($this->current_period_end) : null,
            'trial_ends_at' => $this->trial_ends_at ? human_date($this->trial_ends_at) : null,
            'canceled_at' => $this->canceled_at ? human_date($this->canceled_at) : null,
            'ends_at' => $this->ends_at ? human_date($this->ends_at) : null,
            'plan_details' => $this->whenLoaded('plan', fn () => new SubscriptionPlanResource($this->plan)),
            'created_at' => human_date($this->created_at),
        ];
    }
}
