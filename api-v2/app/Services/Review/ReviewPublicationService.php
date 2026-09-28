<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\NotificationTypeEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\Booking;
use App\Models\Review;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Publication des avis, sans que personne n'ait lu celui de l'autre avant de donner le sien : les deux avis d'une
 * réservation sont publiés ensemble dès que le second est donné, et un avis seul l'est à la fin du délai pour
 * en donner un. Un avis retiré par la modération ne revient jamais.
 */
class ReviewPublicationService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Publie les avis de la réservation si les deux sens sont donnés.
     */
    public function publishIfBothGiven(Booking $booking): void
    {
        $reviews = $booking->reviews()->with('reviewer')->get();

        if ($reviews->count() === 2) {
            $this->publish($reviews->where('is_published', false));
        }
    }

    /**
     * Publie les avis dont le délai est passé ; renvoie leur nombre.
     */
    public function publishDue(): int
    {
        $published = 0;
        $deadline = today()->subDays((int) config('reviews.window_days'));

        Review::query()
            ->where('is_published', false)
            ->whereHas('booking', fn (Builder $booking) => $booking->whereDate('end_date', '<', $deadline))
            ->whereDoesntHave('reports', fn (Builder $reports) => $reports->where('status', ReviewReportStatusEnum::REMOVED))
            ->with('reviewer')
            ->chunkById(100, function (Collection $reviews) use (&$published): void {
                $published += $this->publish($reviews);
            });

        return $published;
    }

    /**
     * @param  Collection<int, Review>  $reviews
     */
    private function publish(Collection $reviews): int
    {
        foreach ($reviews as $review) {
            $review->forceFill(['is_published' => true, 'published_at' => now()])->save();

            if ($review->reviewer !== null) {
                $this->notifications->notify($review->reviewer, NotificationTypeEnum::REVIEW_PUBLISHED, [
                    'review_id' => $review->id,
                    'booking_id' => $review->booking_id,
                    'activity_id' => $review->activity_id,
                ]);
            }
        }

        return $reviews->count();
    }
}
