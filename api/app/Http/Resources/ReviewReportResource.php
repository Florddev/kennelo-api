<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReviewReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReviewReport */
class ReviewReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'review_id' => $this->review_id,
            'reporter_id' => $this->reporter_id,
            'reason' => $this->reason->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'reviewed_at' => $this->reviewed_at ? human_date($this->reviewed_at) : null,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'review' => new ReviewResource($this->whenLoaded('review')),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
