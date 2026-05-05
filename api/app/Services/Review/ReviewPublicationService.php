<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewPublicationService
{
    public const PUBLICATION_DELAY_DAYS = 14;

    public function publishMatured(): int
    {
        $threshold = now()->subDays(self::PUBLICATION_DELAY_DAYS);

        return Review::query()
            ->where('is_published', false)
            ->where('created_at', '<=', $threshold)
            ->update([
                'is_published' => true,
                'published_at' => now(),
            ]);
    }

    public function maybePublishCounterpart(Booking $booking): void
    {
        $reviews = $booking->reviews()->get();

        if ($reviews->count() < 2) {
            return;
        }

        $unpublishedIds = $reviews->where('is_published', false)->pluck('id')->all();

        if (empty($unpublishedIds)) {
            return;
        }

        DB::transaction(function () use ($unpublishedIds): void {
            Review::whereIn('id', $unpublishedIds)->update([
                'is_published' => true,
                'published_at' => now(),
            ]);
        });
    }
}
