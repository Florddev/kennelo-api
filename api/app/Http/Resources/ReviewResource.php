<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\EstablishmentPermission;
use App\Enums\ReviewerType;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser = $request->user();

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'reviewer_id' => $this->reviewer_id,
            'reviewer_type' => $this->reviewer_type->value,
            'overall_rating' => $this->overall_rating,
            'comment' => $this->comment,
            'private_feedback' => $this->when(
                $this->canSeePrivateFeedback($authUser),
                fn () => $this->private_feedback,
            ),
            'would_recommend' => $this->would_recommend,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'criteria_scores' => ReviewCriteriaScoreResource::collection($this->whenLoaded('criteriaScores')),
            'response' => new ReviewResponseResource($this->whenLoaded('response')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function canSeePrivateFeedback(?User $authUser): bool
    {
        if ($authUser === null) {
            return false;
        }

        if ($authUser->hasRole('admin')) {
            return true;
        }

        $booking = $this->resource->booking;

        if ($booking === null) {
            return false;
        }

        if ($this->reviewer_type === ReviewerType::USER) {
            $establishment = $booking->establishment;

            if ($establishment === null) {
                return false;
            }

            if ((string) $establishment->manager_id === (string) $authUser->id) {
                return true;
            }

            return $establishment->collaboratorHasPermission($authUser, EstablishmentPermission::MANAGE_BOOKINGS);
        }

        return (string) $booking->user_id === (string) $authUser->id;
    }
}
