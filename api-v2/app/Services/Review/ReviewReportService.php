<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\AdminActionTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Services\Admin\AdminActionService;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Signalements des avis publiés et leur modération par Kennelo. Retirer un avis le dépublie pour de bon : les
 * autres signalements en attente sur lui sont clos avec la même décision.
 */
class ReviewReportService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
        private readonly AdminActionService $actions,
    ) {}

    /**
     * @param  array{reason: string, description?: string|null}  $data
     */
    public function report(User $reporter, Review $review, array $data): ReviewReport
    {
        if ($review->reports()->where('reporter_id', $reporter->id)->exists()) {
            throw ValidationException::withMessages(['review' => __('reviews.errors.already_reported')]);
        }

        try {
            $report = $review->reports()->create([...$data, 'reporter_id' => $reporter->id]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['review' => __('reviews.errors.already_reported')]);
        }

        $this->notifications->notify($this->recipients->admins(), NotificationTypeEnum::REVIEW_REPORTED, [
            'report_id' => $report->id,
            'review_id' => $review->id,
            'reason' => $report->reason->value,
        ]);

        return $report->refresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return ReviewReport::query()
            ->with(['reporter', 'review.reviewer', 'review.activity'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }

    /**
     * Décide d'un signalement en attente. « removed » dépublie l'avis et clôt ses autres signalements en attente.
     */
    public function decide(User $admin, ReviewReport $report, ReviewReportStatusEnum $decision): ReviewReport
    {
        if ($report->status !== ReviewReportStatusEnum::PENDING) {
            throw ValidationException::withMessages(['status' => __('reviews.errors.already_decided')]);
        }

        $decided = DB::transaction(function () use ($report, $decision): Collection {
            if ($decision !== ReviewReportStatusEnum::REMOVED) {
                $report->forceFill(['status' => $decision, 'reviewed_at' => now()])->save();

                return $report->newCollection([$report]);
            }

            $report->review?->forceFill(['is_published' => false, 'published_at' => null])->save();
            $pending = ReviewReport::query()->where('review_id', $report->review_id)->where('status', ReviewReportStatusEnum::PENDING)->get();
            $pending->each(fn (ReviewReport $closed) => $closed->forceFill(['status' => $decision, 'reviewed_at' => now()])->save());

            return $pending;
        });

        $report->loadMissing('review.reviewer');
        $this->actions->log($admin, $report->review?->reviewer, AdminActionTypeEnum::DECIDE_REVIEW_REPORT, [
            'report_id' => $report->id,
            'review_id' => $report->review_id,
            'decision' => $decision->value,
        ]);

        foreach ($decided as $closed) {
            $closed->loadMissing('reporter');

            if ($closed->reporter !== null) {
                $this->notifications->notify($closed->reporter, NotificationTypeEnum::REVIEW_REPORT_RESOLVED, [
                    'report_id' => $closed->id,
                    'review_id' => $closed->review_id,
                    'status' => $decision->value,
                ]);
            }
        }

        return $report->refresh()->load(['reporter', 'review.reviewer', 'review.activity']);
    }
}
