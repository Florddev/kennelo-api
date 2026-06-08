<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\ActivityPermissionEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\PaginationEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\Review;
use App\Models\ReviewCriteriaDefinition;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(
        private ReviewPublicationService $publicationService,
    ) {}

    public function create(User $actor, Booking $booking, array $data): Review
    {
        if ($booking->status !== BookingStatusEnum::COMPLETED) {
            throw ValidationException::withMessages([
                'status' => ['You can only review a completed booking.'],
            ]);
        }

        $reviewerType = $this->resolveReviewerType($actor, $booking);

        if ($booking->reviews()->where('reviewer_type', $reviewerType->value)->exists()) {
            throw ValidationException::withMessages([
                'booking_id' => ['You have already submitted a review for this booking.'],
            ]);
        }

        $criteriaScores = $data['criteria_scores'] ?? [];
        $this->validateCriteria($criteriaScores, $reviewerType);

        try {
            $review = DB::transaction(function () use ($actor, $booking, $reviewerType, $data, $criteriaScores): Review {
                $review = Review::create([
                    'booking_id' => $booking->id,
                    'reviewer_id' => $actor->id,
                    'reviewer_type' => $reviewerType->value,
                    'overall_rating' => $data['overall_rating'],
                    'comment' => $data['comment'] ?? null,
                    'private_feedback' => $data['private_feedback'] ?? null,
                    'would_recommend' => $data['would_recommend'] ?? true,
                    'is_published' => false,
                    'published_at' => null,
                ]);

                foreach ($criteriaScores as $score) {
                    $review->criteriaScores()->create([
                        'criteria_code' => $score['code'],
                        'score' => $score['score'],
                    ]);
                }

                return $review;
            });
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                throw ValidationException::withMessages([
                    'booking_id' => ['You have already submitted a review for this booking.'],
                ]);
            }
            throw $e;
        }

        $this->publicationService->maybePublishCounterpart($booking);

        return $review->fresh(['criteriaScores', 'reviewer']);
    }

    public function show(Review $review): Review
    {
        return $review->load(['reviewer', 'criteriaScores.definition', 'response.responder', 'booking']);
    }

    public function forActivity(Activity $activity, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['reviewer', 'booking.activity', 'criteriaScores.definition', 'response.responder'])
            ->whereHas('booking', fn ($q) => $q->where('activity_id', $activity->id))
            ->where('reviewer_type', ReviewerTypeEnum::USER->value)
            ->where('is_published', true)
            ->when(isset($filters['min_rating']), fn ($q) => $q->where('overall_rating', '>=', $filters['min_rating']))
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function forUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['reviewer', 'booking.activity', 'criteriaScores.definition', 'response.responder'])
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value)
            ->where('is_published', true)
            ->when(isset($filters['min_rating']), fn ($q) => $q->where('overall_rating', '>=', $filters['min_rating']))
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function mineGiven(User $actor, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['booking.activity', 'criteriaScores.definition', 'response'])
            ->where('reviewer_id', $actor->id)
            ->latest()
            ->paginate($perPage);
    }

    public function mineReceived(User $actor, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['reviewer', 'booking.activity', 'criteriaScores.definition', 'response'])
            ->whereHas('booking', fn ($q) => $q->where('user_id', $actor->id))
            ->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value)
            ->latest()
            ->paginate($perPage);
    }

    public function forPet(Pet $pet, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['reviewer', 'criteriaScores.definition', 'response.responder'])
            ->whereHas('booking', fn ($q) => $q->whereHas('pets', fn ($pq) => $pq->where('pets.id', $pet->id)))
            ->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value)
            ->where('is_published', true)
            ->when(isset($filters['min_rating']), fn ($q) => $q->where('overall_rating', '>=', $filters['min_rating']))
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function aggregatesForPet(Pet $pet): array
    {
        $base = Review::query()
            ->whereHas('booking', fn ($q) => $q->whereHas('pets', fn ($pq) => $pq->where('pets.id', $pet->id)))
            ->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value)
            ->where('is_published', true);

        $total = (clone $base)->count();
        $average = $total > 0 ? round((float) (clone $base)->avg('overall_rating'), 2) : null;

        return [
            'total' => $total,
            'average' => $average,
        ];
    }

    public function aggregatesForActivity(Activity $activity): array
    {
        $base = Review::query()
            ->whereHas('booking', fn ($q) => $q->where('activity_id', $activity->id))
            ->where('reviewer_type', ReviewerTypeEnum::USER->value)
            ->where('is_published', true);

        $total = (clone $base)->count();
        $average = $total > 0 ? round((float) (clone $base)->avg('overall_rating'), 2) : null;

        return [
            'total' => $total,
            'average' => $average,
        ];
    }

    public function aggregatesForUser(User $user): array
    {
        $base = Review::query()
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value)
            ->where('is_published', true);

        $total = (clone $base)->count();
        $average = $total > 0 ? round((float) (clone $base)->avg('overall_rating'), 2) : null;

        return [
            'total' => $total,
            'average' => $average,
        ];
    }

    public function resolveReviewerType(User $actor, Booking $booking): ReviewerTypeEnum
    {
        if ((string) $booking->user_id === (string) $actor->id) {
            return ReviewerTypeEnum::USER;
        }

        $booking->loadMissing('activity');
        $activity = $booking->activity;

        if ($activity === null) {
            throw ValidationException::withMessages([
                'booking_id' => ['You are not allowed to review this booking.'],
            ]);
        }

        if ((string) $activity->manager_id === (string) $actor->id) {
            return ReviewerTypeEnum::ACTIVITY;
        }

        if ($activity->collaboratorHasPermission($actor, ActivityPermissionEnum::MANAGE_BOOKINGS)) {
            return ReviewerTypeEnum::ACTIVITY;
        }

        throw ValidationException::withMessages([
            'booking_id' => ['You are not allowed to review this booking.'],
        ]);
    }

    private function validateCriteria(array $criteriaScores, ReviewerTypeEnum $reviewerType): void
    {
        if (empty($criteriaScores)) {
            return;
        }

        $codes = array_unique(array_column($criteriaScores, 'code'));

        if (count($codes) !== count($criteriaScores)) {
            throw ValidationException::withMessages([
                'criteria_scores' => ['Duplicate criteria are not allowed.'],
            ]);
        }

        $definitions = ReviewCriteriaDefinition::whereIn('code', $codes)->get()->keyBy('code');

        foreach ($criteriaScores as $score) {
            $definition = $definitions->get($score['code']);

            if ($definition === null) {
                throw ValidationException::withMessages([
                    'criteria_scores' => ["Unknown criteria code: {$score['code']}."],
                ]);
            }

            if (! $definition->applicable_to->matchesTarget($reviewerType)) {
                throw ValidationException::withMessages([
                    'criteria_scores' => ["Criteria '{$score['code']}' is not applicable to this review."],
                ]);
            }
        }
    }
}
