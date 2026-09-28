<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Avis sur les réservations terminées : le client note l'activité, l'équipe note le client. Chacun a un délai
 * après la fin de la réservation pour donner le sien (config reviews.window_days) ; les avis sont publiés par
 * ReviewPublicationService. Les moyennes se calculent à la lecture, sur les avis publiés.
 */
class ReviewService
{
    public const array RELATIONS = ['reviewer.media', 'activity.organization', 'booking', 'response.responder'];

    public function __construct(
        private readonly ReviewPublicationService $publication,
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * @param  array{overall_rating: numeric-string|float|int, comment?: string|null, private_feedback?: string|null, would_recommend?: bool}  $data
     */
    public function create(User $author, Booking $booking, array $data): Review
    {
        if ($booking->status !== BookingStatusEnum::COMPLETED) {
            throw ValidationException::withMessages(['booking' => __('reviews.errors.not_completed')]);
        }

        if ($booking->end_date->copy()->addDays((int) config('reviews.window_days'))->isBefore(today())) {
            throw ValidationException::withMessages(['booking' => __('reviews.errors.window_closed', ['days' => config('reviews.window_days')])]);
        }

        $type = $booking->user_id === $author->id ? ReviewerTypeEnum::USER : ReviewerTypeEnum::ACTIVITY;

        if ($booking->reviews()->where('reviewer_type', $type)->exists()) {
            throw ValidationException::withMessages(['booking' => __('reviews.errors.already_reviewed')]);
        }

        // L'index unique (réservation, sens) départage deux envois simultanés.
        try {
            $review = $booking->reviews()->create([
                'activity_id' => $booking->activity_id,
                'reviewer_id' => $author->id,
                'reviewer_type' => $type,
                'overall_rating' => $data['overall_rating'],
                'comment' => $data['comment'] ?? null,
                'private_feedback' => $data['private_feedback'] ?? null,
                'would_recommend' => $data['would_recommend'] ?? true,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['booking' => __('reviews.errors.already_reviewed')]);
        }

        $this->notifyReviewed($review, $booking);
        $this->publication->publishIfBothGiven($booking);

        return $review->refresh()->load(self::RELATIONS);
    }

    /**
     * Avis publiés des clients sur l'activité, les plus récents d'abord.
     *
     * @param  array<string, mixed>  $filters
     */
    public function forActivity(Activity $activity, array $filters = []): LengthAwarePaginator
    {
        return $this->listed($this->publishedForActivity($activity), $filters);
    }

    /**
     * Avis publiés des équipes sur le client.
     *
     * @param  array<string, mixed>  $filters
     */
    public function aboutClient(User $client, array $filters = []): LengthAwarePaginator
    {
        return $this->listed($this->aboutClientQuery($client)->published(), $filters);
    }

    /**
     * Avis que la personne a donnés, en tant que client ou pour une équipe, publiés ou non.
     *
     * @param  array<string, mixed>  $filters
     */
    public function given(User $author, array $filters = []): LengthAwarePaginator
    {
        return $this->listed(Review::query()->where('reviewer_id', $author->id), $filters, 'created_at');
    }

    /**
     * Note moyenne, nombre d'avis publiés et répartition par étoile (la note arrondie).
     *
     * @param  Builder<Review>  $published
     * @return array{average: float|null, count: int, distribution: array<int, int>}
     */
    public function rating(Builder $published): array
    {
        $totals = (clone $published)->toBase()->selectRaw('count(*) as total, avg(overall_rating) as average')->first();
        $buckets = (clone $published)->toBase()
            ->selectRaw('cast(round(overall_rating) as integer) as stars, count(*) as total')
            ->groupByRaw('cast(round(overall_rating) as integer)')
            ->pluck('total', 'stars');

        return [
            'average' => ($totals->total ?? 0) > 0 ? round((float) $totals->average, 1) : null,
            'count' => (int) ($totals->total ?? 0),
            'distribution' => collect(range(5, 1))->mapWithKeys(fn (int $stars): array => [$stars => (int) ($buckets[$stars] ?? 0)])->all(),
        ];
    }

    /**
     * @return Builder<Review>
     */
    public function publishedForActivity(Activity $activity): Builder
    {
        return Review::query()->byClients()->published()->where('activity_id', $activity->id);
    }

    /**
     * @return Builder<Review>
     */
    public function aboutClientQuery(User $client): Builder
    {
        return Review::query()->aboutClients()->whereHas('booking', fn (Builder $booking) => $booking->where('user_id', $client->id));
    }

    /**
     * Réponse publique de la partie notée, une seule par avis.
     */
    public function respond(User $responder, Review $review, string $response): ReviewResponse
    {
        if ($review->response()->exists()) {
            throw ValidationException::withMessages(['response' => __('reviews.errors.already_answered')]);
        }

        try {
            $created = $review->response()->create(['responder_id' => $responder->id, 'response' => $response]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['response' => __('reviews.errors.already_answered')]);
        }

        if ($review->reviewer !== null) {
            $this->notifications->notify($review->reviewer, NotificationTypeEnum::REVIEW_RESPONSE, [
                'review_id' => $review->id,
                'activity_id' => $review->activity_id,
            ]);
        }

        return $created;
    }

    /**
     * La partie notée apprend qu'un avis l'attend, sans le lire : elle le découvrira en donnant le sien.
     */
    private function notifyReviewed(Review $review, Booking $booking): void
    {
        $booking->loadMissing(['user', 'organization', 'activity']);
        $data = ['review_id' => $review->id, 'booking_id' => $booking->id, 'activity_id' => $booking->activity_id, 'activity_name' => $booking->activity?->name];

        if ($review->reviewer_type === ReviewerTypeEnum::ACTIVITY) {
            if ($booking->user !== null) {
                $this->notifications->notify($booking->user, NotificationTypeEnum::REVIEW_RECEIVED, $data);
            }

            return;
        }

        if ($booking->organization !== null) {
            $this->notifications->notify(
                $this->recipients->membersAllowedTo(OrganizationPermissionEnum::BOOKINGS_MANAGE, $booking->organization, $booking->activity_id),
                NotificationTypeEnum::REVIEW_RECEIVED,
                $data,
            );
        }
    }

    /**
     * @param  Builder<Review>  $query
     * @param  array<string, mixed>  $filters
     */
    private function listed(Builder $query, array $filters, string $sort = 'published_at'): LengthAwarePaginator
    {
        return $query
            ->with(self::RELATIONS)
            ->when(isset($filters['min_rating']), fn (Builder $query) => $query->where('overall_rating', '>=', $filters['min_rating']))
            ->orderByDesc($sort)
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }
}
