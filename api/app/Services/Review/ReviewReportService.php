<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\PaginationEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReviewReportService
{
    public function create(User $reporter, Review $review, array $data): ReviewReport
    {
        if ((string) $review->reviewer_id === (string) $reporter->id) {
            throw ValidationException::withMessages([
                'review_id' => ['You cannot report your own review.'],
            ]);
        }

        $alreadyReported = ReviewReport::query()
            ->where('review_id', $review->id)
            ->where('reporter_id', $reporter->id)
            ->exists();

        if ($alreadyReported) {
            throw ValidationException::withMessages([
                'review_id' => ['You have already reported this review.'],
            ]);
        }

        return ReviewReport::create([
            'review_id' => $review->id,
            'reporter_id' => $reporter->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => ReviewReportStatusEnum::PENDING->value,
        ]);
    }

    public function listAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return ReviewReport::query()
            ->with(['reporter', 'review.reviewer', 'review.booking'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function updateStatus(ReviewReport $report, string $status): ReviewReport
    {
        $report->update([
            'status' => $status,
            'reviewed_at' => now(),
        ]);

        return $report->fresh(['reporter', 'review']);
    }
}
