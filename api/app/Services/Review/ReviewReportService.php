<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\NotificationTypeEnum;
use App\Enums\PaginationEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReviewReportService
{
    public function __construct(
        private NotificationService $notifications,
        private NotificationRecipientResolver $recipients
    ) {}

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

        $report = ReviewReport::create([
            'review_id' => $review->id,
            'reporter_id' => $reporter->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => ReviewReportStatusEnum::PENDING->value,
        ]);

        $this->notifications->notify(
            $this->recipients->admins(),
            NotificationTypeEnum::REVIEW_REPORTED,
            [
                'report_id' => $report->id,
                'review_id' => $review->id,
                'reporter_id' => $reporter->id,
            ],
        );

        return $report;
    }

    public function listAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return ReviewReport::query()
            ->with(['reporter.media', 'review.reviewer.media', 'review.booking.activity'])
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

        $report->loadMissing('reporter');

        if ($report->reporter !== null) {
            $this->notifications->notify(
                $report->reporter,
                NotificationTypeEnum::REVIEW_REPORT_RESOLVED,
                [
                    'report_id' => $report->id,
                    'review_id' => $report->review_id,
                    'status' => $status,
                ],
            );
        }

        return $report->fresh(['reporter', 'review']);
    }
}
