<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReviewReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un signalement. L'admin voit l'avis signalé en entier, avec son auteur.
 *
 * @mixin ReviewReport
 */
class ReviewReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'review_id' => $this->review_id,
            'reason' => $this->reason->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'reporter' => $this->whenLoaded('reporter', fn (): ?array => $this->reporter === null ? null : [
                'id' => $this->reporter->id,
                'first_name' => $this->reporter->first_name,
                'last_name' => $this->reporter->last_name,
            ]),
            'review' => $this->whenLoaded('review', fn (): ?array => $this->review === null ? null : [
                'id' => $this->review->id,
                'reviewer_type' => $this->review->reviewer_type->value,
                'reviewer' => $this->review->reviewer === null ? null : [
                    'id' => $this->review->reviewer->id,
                    'first_name' => $this->review->reviewer->first_name,
                    'last_name' => $this->review->reviewer->last_name,
                ],
                'activity' => ['id' => $this->review->activity_id, 'name' => $this->review->activity?->name],
                'overall_rating' => $this->review->overall_rating,
                'comment' => $this->review->comment,
                'is_published' => $this->review->is_published,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
