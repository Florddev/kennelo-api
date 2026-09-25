<?php

declare(strict_types=1);

namespace App\Services\Admin\Activity;

use App\Enums\ActivityStatusEnum;
use App\Enums\DocumentStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\User;
use App\Services\Activity\ActivityService;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Revue des activités et de leurs justificatifs par l'équipe Kennelo.
 */
class ActivityReviewService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Activity::query()
            ->with(ActivityService::RELATIONS)
            ->withExists(Activity::missingDocumentsCheck())
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['profession_id']), fn (Builder $query) => $query->where('profession_id', $filters['profession_id']))
            ->when(isset($filters['search']), fn (Builder $query) => $query->where('name', 'like', "%{$filters['search']}%"))
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateDocuments(array $filters = []): LengthAwarePaginator
    {
        return ActivityDocument::query()
            ->with(['activity.organization', 'media'])
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['activity_id']), fn (Builder $query) => $query->where('activity_id', $filters['activity_id']))
            ->oldest()
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? null);
    }

    /**
     * Une activité n'est approuvée qu'avec tous les justificatifs obligatoires de son métier, valables.
     */
    public function approve(Activity $activity, User $admin): Activity
    {
        if (Activity::query()->whereKey($activity->id)->missingRequiredDocuments()->exists()) {
            throw ValidationException::withMessages(['activity' => __('activity.missing_documents')]);
        }

        $this->review($activity, $admin, ActivityStatusEnum::APPROVED, null);
        $this->notifyTeam($activity, NotificationTypeEnum::ACTIVITY_APPROVED);

        return $activity;
    }

    public function reject(Activity $activity, User $admin, string $reason): Activity
    {
        $this->review($activity, $admin, ActivityStatusEnum::REJECTED, $reason);
        $this->notifyTeam($activity, NotificationTypeEnum::ACTIVITY_REJECTED, ['reason' => $reason]);

        return $activity;
    }

    public function suspend(Activity $activity, User $admin, string $reason): Activity
    {
        $this->review($activity, $admin, ActivityStatusEnum::SUSPENDED, $reason);
        $this->notifyTeam($activity, NotificationTypeEnum::ACTIVITY_SUSPENDED, ['reason' => $reason]);

        return $activity;
    }

    public function approveDocument(ActivityDocument $document, User $admin): ActivityDocument
    {
        if ($document->expires_at?->isBefore(today())) {
            throw ValidationException::withMessages(['document' => __('activity.document_already_expired')]);
        }

        $this->reviewDocument($document, $admin, DocumentStatusEnum::APPROVED, null);
        $this->notifyDocumentTeam($document, NotificationTypeEnum::ACTIVITY_DOCUMENT_APPROVED);

        return $document;
    }

    public function rejectDocument(ActivityDocument $document, User $admin, string $reason): ActivityDocument
    {
        $this->reviewDocument($document, $admin, DocumentStatusEnum::REJECTED, $reason);
        $this->notifyDocumentTeam($document, NotificationTypeEnum::ACTIVITY_DOCUMENT_REJECTED, ['reason' => $reason]);

        return $document;
    }

    private function review(Activity $activity, User $admin, ActivityStatusEnum $status, ?string $reason): void
    {
        $activity->forceFill([
            'status' => $status,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }

    private function reviewDocument(ActivityDocument $document, User $admin, DocumentStatusEnum $status, ?string $reason): void
    {
        $document->forceFill([
            'status' => $status,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, string>  $data
     */
    private function notifyTeam(Activity $activity, NotificationTypeEnum $type, array $data = []): void
    {
        if ($activity->organization === null) {
            return;
        }

        $this->notifications->notify(
            $this->recipients->membersAllowedTo(OrganizationPermissionEnum::ACTIVITY_MANAGE, $activity->organization, $activity->id),
            $type,
            ['activity_id' => $activity->id, 'activity_name' => $activity->name, ...$data],
        );
    }

    /**
     * @param  array<string, string>  $data
     */
    private function notifyDocumentTeam(ActivityDocument $document, NotificationTypeEnum $type, array $data = []): void
    {
        if ($document->activity === null) {
            return;
        }

        $this->notifyTeam($document->activity, $type, [
            'document_id' => $document->id,
            'document_type' => $document->document_type->value,
            ...$data,
        ]);
    }
}
