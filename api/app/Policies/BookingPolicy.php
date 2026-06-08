<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActivityPermissionEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ((string) $booking->user_id === (string) $user->id) {
            return true;
        }

        $booking->loadMissing('activity');
        $activity = $booking->activity;

        if ($activity === null) {
            return false;
        }

        if ((string) $activity->manager_id === (string) $user->id) {
            return true;
        }

        return $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_BOOKINGS);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ((string) $booking->user_id !== (string) $user->id) {
            return false;
        }

        return $booking->status === BookingStatusEnum::PENDING || $booking->status === BookingStatusEnum::CONFIRMED;
    }

    public function manageForActivity(User $user, Activity $activity): bool
    {
        if ((string) $activity->manager_id === (string) $user->id) {
            return true;
        }

        return $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_BOOKINGS);
    }
}
