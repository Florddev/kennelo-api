<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\CollaboratorStatusEnum;
use App\Models\Activity;
use App\Models\ActivityCollaborator;
use App\Models\ActivityRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CollaboratorService
{
    public function invite(Activity $activity, User $user): ActivityCollaborator
    {
        return DB::transaction(function () use ($activity, $user): ActivityCollaborator {
            $activity->collaboratorLinks()->where('user_id', $user->id)->delete();

            $activity->collaboratorLinks()->create([
                'user_id' => $user->id,
                'status' => CollaboratorStatusEnum::PENDING->value,
                'invited_at' => now(),
            ]);

            return $this->link($activity, $user);
        });
    }

    public function accept(Activity $activity, User $user): ActivityCollaborator
    {
        $activity->collaboratorLinks()->where('user_id', $user->id)->update([
            'status' => CollaboratorStatusEnum::ACCEPTED->value,
            'responded_at' => now(),
        ]);

        return $this->link($activity, $user);
    }

    public function decline(Activity $activity, User $user): ActivityCollaborator
    {
        $activity->collaboratorLinks()->where('user_id', $user->id)->update([
            'status' => CollaboratorStatusEnum::REFUSED->value,
            'responded_at' => now(),
        ]);

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
}
