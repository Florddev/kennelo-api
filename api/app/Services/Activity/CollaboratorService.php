<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\CollaboratorStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Activity;
use App\Models\ActivityCollaborator;
use App\Models\ActivityRole;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;

class CollaboratorService
{
    public function __construct(
        private NotificationService $notifications
    ) {}

    public function invite(Activity $activity, User $user): ActivityCollaborator
    {
        $link = DB::transaction(function () use ($activity, $user): ActivityCollaborator {
            $activity->collaboratorLinks()->where('user_id', $user->id)->delete();

            $activity->collaboratorLinks()->create([
                'user_id' => $user->id,
                'status' => CollaboratorStatusEnum::PENDING->value,
                'invited_at' => now(),
            ]);

            return $this->link($activity, $user);
        });

        $this->notifications->notify($user, NotificationTypeEnum::COLLABORATOR_INVITED, $this->payload($activity));

        return $link;
    }

    public function accept(Activity $activity, User $user): ActivityCollaborator
    {
        $activity->collaboratorLinks()
            ->where('user_id', $user->id)
            ->where('status', CollaboratorStatusEnum::PENDING->value)
            ->update([
                'status' => CollaboratorStatusEnum::ACCEPTED->value,
                'responded_at' => now(),
            ]);

        $this->notifyManager($activity, $user, NotificationTypeEnum::COLLABORATOR_ACCEPTED);

        return $this->link($activity, $user);
    }

    public function decline(Activity $activity, User $user): ActivityCollaborator
    {
        $activity->collaboratorLinks()
            ->where('user_id', $user->id)
            ->where('status', CollaboratorStatusEnum::PENDING->value)
            ->update([
                'status' => CollaboratorStatusEnum::REFUSED->value,
                'responded_at' => now(),
            ]);

        $this->notifyManager($activity, $user, NotificationTypeEnum::COLLABORATOR_DECLINED);

        return $this->link($activity, $user);
    }

    public function assignRole(Activity $activity, User $user, ActivityRole $role): ActivityCollaborator
    {
        $activity->collaboratorLinks()->where('user_id', $user->id)->update([
            'role_id' => $role->id,
        ]);

        return $this->link($activity, $user);
    }

    public function remove(Activity $activity, User $user): void
    {
        $activity->collaboratorLinks()->where('user_id', $user->id)->delete();
    }

    private function link(Activity $activity, User $user): ActivityCollaborator
    {
        return ActivityCollaborator::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->with(['user', 'role.permissions'])
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Activity $activity): array
    {
        return [
            'activity_id' => $activity->id,
            'activity_name' => $activity->name,
        ];
    }

    private function notifyManager(Activity $activity, User $collaborator, NotificationTypeEnum $type): void
    {
        $manager = $activity->manager;

        if ($manager === null) {
            return;
        }

        $this->notifications->notify($manager, $type, array_merge($this->payload($activity), [
            'user_id' => $collaborator->id,
        ]));
    }
}
