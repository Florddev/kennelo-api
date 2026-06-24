<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActivityPermissionEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if ((string) $conversation->user_id === (string) $user->id) {
            return true;
        }

        $conversation->loadMissing('activity');
        $activity = $conversation->activity;

        if ($activity === null) {
            return false;
        }

        if ((string) $activity->manager_id === (string) $user->id) {
            return true;
        }

        return $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_MESSAGES);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    public function manageForActivity(User $user, Activity $activity): bool
    {
        if ((string) $activity->manager_id === (string) $user->id) {
            return true;
        }

        return $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_MESSAGES);
    }

    public function createForBooking(User $user, Booking $booking): bool
    {
        if ((string) $booking->user_id === (string) $user->id) {
            return true;
        }

        $booking->loadMissing('activity');

        return $booking->activity !== null
            && (string) $booking->activity->manager_id === (string) $user->id;
    }

    public function createForActivityAsGuest(User $user, Activity $activity): bool
    {
        return (string) $activity->manager_id !== (string) $user->id;
    }
}
