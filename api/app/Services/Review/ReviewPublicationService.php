<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\NotificationTypeEnum;
use App\Models\Booking;
use App\Models\Review;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReviewPublicationService
{
    public const PUBLICATION_DELAY_DAYS = 14;

    public function __construct(
        private NotificationService $notifications
    ) {}

    public function publishMatured(): int
    {
        $threshold = now()->subDays(self::PUBLICATION_DELAY_DAYS);

        $matured = Review::query()
            ->with('reviewer')
            ->where('is_published', false)
            ->where('created_at', '<=', $threshold)
            ->get();

        if ($matured->isEmpty()) {
            return 0;
        }

        Review::whereIn('id', $matured->pluck('id')->all())
            ->where('is_published', false)
            ->update([
                'is_published' => true,
                'published_at' => now(),
            ]);

        $this->notifyPublished($matured);

        return $matured->count();
    }

    public function maybePublishCounterpart(Booking $booking): void
    {
        $reviews = Review::query()->with('reviewer')->where('booking_id', $booking->id)->get();

        if ($reviews->count() < 2) {
            return;
        }

        $unpublished = $reviews->where('is_published', false);

        if ($unpublished->isEmpty()) {
            return;
        }

        $unpublishedIds = $unpublished->pluck('id')->all();

        DB::transaction(function () use ($unpublishedIds): void {
            Review::whereIn('id', $unpublishedIds)->update([
                'is_published' => true,
                'published_at' => now(),
            ]);
        });

        $this->notifyPublished($unpublished);
    }

    /**
     * @param  Collection<int, Review>  $reviews
     */
    private function notifyPublished(Collection $reviews): void
    {
        foreach ($reviews as $review) {
            if ($review->reviewer === null) {
                continue;
            }

            $this->notifications->notify(
                $review->reviewer,
                NotificationTypeEnum::REVIEW_PUBLISHED,
                [
                    'review_id' => $review->id,
                    'booking_id' => $review->booking_id,
                ],
            );
        }
    }
}
