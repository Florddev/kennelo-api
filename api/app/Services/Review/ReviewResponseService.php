<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\ActivityPermissionEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ReviewResponseService
{
    public function create(User $actor, Review $review, array $data): ReviewResponse
    {
        if ($review->response()->exists()) {
            throw ValidationException::withMessages([
                'review_id' => ['A response has already been submitted for this review.'],
            ]);
        }

        if (! $this->canRespond($actor, $review)) {
            throw ValidationException::withMessages([
                'review_id' => ['You are not allowed to respond to this review.'],
            ]);
        }

        try {
            return ReviewResponse::create([
                'review_id' => $review->id,
                'responder_id' => $actor->id,
                'response' => $data['response'],
            ]);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                throw ValidationException::withMessages([
                    'review_id' => ['A response has already been submitted for this review.'],
                ]);
            }
            throw $e;
        }
    }

    public function canRespond(User $actor, Review $review): bool
    {
        $booking = $review->booking;

        if ($booking === null) {
            return false;
        }

        if ($review->reviewer_type === ReviewerTypeEnum::USER) {
            $establishment = $booking->establishment;

            if ($establishment === null) {
                return false;
            }

            if ((string) $establishment->manager_id === (string) $actor->id) {
                return true;
            }

            return $establishment->collaboratorHasPermission($actor, ActivityPermissionEnum::MANAGE_BOOKINGS);
        }

        return (string) $booking->user_id === (string) $actor->id;
    }
}
