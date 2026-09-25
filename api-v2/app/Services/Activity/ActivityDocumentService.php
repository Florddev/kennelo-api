<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\DocumentStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Justificatifs d'une activité : dépôt par l'équipe, puis suivi des échéances par la tâche quotidienne
 * activities:expire-documents. La revue par Kennelo est dans App\Services\Admin\Activity\ActivityReviewService.
 */
class ActivityDocumentService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * @return Collection<int, ActivityDocument>
     */
    public function forActivity(Activity $activity): Collection
    {
        return $activity->documents()->with('media')->latest()->orderByDesc('id')->get();
    }

    /**
     * Un nouveau dépôt ne remplace pas l'ancien : l'historique est conservé, et le plus récent valable compte.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(Activity $activity, array $data, UploadedFile $file): ActivityDocument
    {
        return DB::transaction(function () use ($activity, $data, $file): ActivityDocument {
            $document = $activity->documents()->create($data);
            $document->addMedia($file)->toMediaCollection(ActivityDocument::COLLECTION_FILE);

            return $document->refresh()->load('media');
        });
    }

    /**
     * Passe en expiré les justificatifs approuvés dont l'échéance est dépassée. L'activité n'a pas à être
     * suspendue : faute de justificatif valable, elle sort d'elle-même de Activity::bookable().
     */
    public function expireOverdue(): int
    {
        $expired = 0;

        ActivityDocument::query()
            ->where('status', DocumentStatusEnum::APPROVED)
            ->whereDate('expires_at', '<', today())
            ->with('activity.organization')
            ->lazyById()
            ->each(function (ActivityDocument $document) use (&$expired): void {
                $document->forceFill(['status' => DocumentStatusEnum::EXPIRED])->save();
                $this->notifyTeam($document, NotificationTypeEnum::ACTIVITY_DOCUMENT_EXPIRED);
                $expired++;
            });

        return $expired;
    }

    /**
     * Prévient l'équipe des justificatifs qui arrivent à échéance dans le délai configuré. La tâche passant
     * une fois par jour, chaque justificatif n'est signalé qu'une fois.
     */
    public function notifyExpiringSoon(): int
    {
        $notified = 0;

        ActivityDocument::query()
            ->where('status', DocumentStatusEnum::APPROVED)
            ->whereDate('expires_at', today()->addDays((int) config('activities.documents.expiry_notice_days')))
            ->with('activity.organization')
            ->lazyById()
            ->each(function (ActivityDocument $document) use (&$notified): void {
                $this->notifyTeam($document, NotificationTypeEnum::ACTIVITY_DOCUMENT_EXPIRING);
                $notified++;
            });

        return $notified;
    }

    private function notifyTeam(ActivityDocument $document, NotificationTypeEnum $type): void
    {
        $activity = $document->activity;

        if ($activity?->organization === null) {
            return;
        }

        $this->notifications->notify(
            $this->recipients->membersAllowedTo(OrganizationPermissionEnum::ACTIVITY_MANAGE, $activity->organization, $activity->id),
            $type,
            [
                'activity_id' => $activity->id,
                'activity_name' => $activity->name,
                'document_id' => $document->id,
                'document_type' => $document->document_type->value,
                'expires_at' => $document->expires_at?->toDateString(),
            ],
        );
    }
}
