<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\ActivityPermissionEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaginationEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\Review;
use App\Models\ReviewCriteriaDefinition;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(
        private ReviewPublicationService $publicationService,
        private NotificationService $notifications,
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

        $recipient = $this->resolveReviewRecipient($booking, $reviewerType);

        if ($recipient !== null) {
            $this->notifications->notify(
                $recipient,
                NotificationTypeEnum::REVIEW_RECEIVED,
                [
                    'review_id' => $review->id,
                    'booking_id' => $booking->id,
                    'reviewer_type' => $reviewerType->value,
                ],
            );
        }

        return $review->fresh(['criteriaScores', 'reviewer.media', 'booking.activity']);
    }

    private function resolveReviewRecipient(Booking $booking, ReviewerTypeEnum $reviewerType): ?User
    {
        if ($reviewerType === ReviewerTypeEnum::USER) {
            $booking->loadMissing('activity.manager');

            return $booking->activity?->manager;
        }

        $booking->loadMissing('user');

        return $booking->user;
    }

    public function show(Review $review): Review
    {
        return $review->load(['reviewer.media', 'criteriaScores.definition', 'response.responder.media', 'booking.activity']);
    }

    public function forActivity(Activity $activity, ?User $viewer, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        $reviews = Review::query()
            ->with(['reviewer.media', 'booking.activity', 'criteriaScores.definition', 'response.responder.media'])
            ->whereHas('booking', fn ($q) => $q->where('activity_id', $activity->id))
            ->where('reviewer_type', ReviewerTypeEnum::USER->value)
            ->where('is_published', true)
            ->when(isset($filters['min_rating']), fn ($q) => $q->where('overall_rating', '>=', $filters['min_rating']))
            ->latest('published_at')
            ->paginate($perPage);

        $canSeePrivateFeedback = $this->canViewerSeeActivityPrivateFeedback($activity, $viewer);

        $reviews->getCollection()->each(function (Review $review) use ($canSeePrivateFeedback): void {
            $review->setAttribute('viewer_can_see_private_feedback', $canSeePrivateFeedback);
        });

        return $reviews;
    }

    private function canViewerSeeActivityPrivateFeedback(Activity $activity, ?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        if ($viewer->hasRole('admin')) {
            return true;
        }

        if ((string) $activity->manager_id === (string) $viewer->id) {
            return true;
        }

        return $activity->collaboratorHasPermission($viewer, ActivityPermissionEnum::MANAGE_BOOKINGS);
    }

    public function forUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['reviewer.media', 'booking.activity', 'criteriaScores.definition', 'response.responder.media'])
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
            ->with(['reviewer.media', 'booking.activity', 'criteriaScores.definition', 'response'])
            ->whereHas('booking', fn ($q) => $q->where('user_id', $actor->id))
            ->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value)
            ->latest()
            ->paginate($perPage);
    }

    public function forPet(Pet $pet, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Review::query()
            ->with(['reviewer.media', 'booking.activity', 'criteriaScores.definition', 'response.responder.media'])
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
        if (blank($criteriaScores)) {
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
