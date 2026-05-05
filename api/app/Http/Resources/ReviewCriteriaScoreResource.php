<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReviewCriteriaScore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReviewCriteriaScore */
class ReviewCriteriaScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'criteria_code' => $this->criteria_code,
            'score' => $this->score,
            'definition' => new ReviewCriteriaDefinitionResource($this->whenLoaded('definition')),
        ];
    }
}
