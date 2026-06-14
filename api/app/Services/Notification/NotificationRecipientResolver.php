<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\ActivityPermissionEnum;
use App\Models\Activity;
use App\Models\ActivityCollaborator;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class NotificationRecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public function forActivity(Activity $activity, ActivityPermissionEnum $permission): Collection
    {
        $ids = collect([$activity->manager_id])
            ->merge($this->collaboratorIdsWithPermission($activity, $permission))
            ->filter()
            ->unique();

        return User::whereIn('id', $ids)->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function forMessage(Message $message): Collection
    {
        $message->loadMissing('conversation.activity');

        $conversation = $message->conversation;
        $senderId = (string) $message->sender_id;
        $ids = collect();

        if ($senderId !== (string) $conversation->user_id) {
            $ids->push($conversation->user_id);
        }

        $activity = $conversation->activity;

        if ($activity !== null) {
            if ($senderId !== (string) $activity->manager_id) {
                $ids->push($activity->manager_id);
            }

            $collaboratorIds = $this->collaboratorIdsWithPermission($activity, ActivityPermissionEnum::MANAGE_MESSAGES)
                ->reject(fn (string $id): bool => $id === $senderId);

            $ids = $ids->merge($collaboratorIds);
        }

        $ids = $ids->filter()->unique();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return User::whereIn('id', $ids)->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::role('admin')->get();
    }

    /**
     * @return SupportCollection<int, string>
     */
    private function collaboratorIdsWithPermission(Activity $activity, ActivityPermissionEnum $permission): SupportCollection
    {
        return ActivityCollaborator::query()
            ->where('activity_id', $activity->id)
            ->accepted()
            ->withPermission($permission)
            ->pluck('user_id');
    }
}
