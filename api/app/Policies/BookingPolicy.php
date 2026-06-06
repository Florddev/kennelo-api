<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BookingStatusEnum;
use App\Enums\EstablishmentPermissionEnum;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ((string) $booking->user_id === (string) $user->id) {
            return true;
        }

        $booking->loadMissing('establishment');
        $establishment = $booking->establishment;

        if ($establishment === null) {
            return false;
        }

        if ((string) $establishment->manager_id === (string) $user->id) {
            return true;
        }

        return $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_BOOKINGS);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ((string) $booking->user_id !== (string) $user->id) {
            return false;
        }

        return $booking->status === BookingStatusEnum::PENDING || $booking->status === BookingStatusEnum::CONFIRMED;
    }

    public function manageForEstablishment(User $user, Establishment $establishment): bool
    {
        if ((string) $establishment->manager_id === (string) $user->id) {
            return true;
        }

        return $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_BOOKINGS);
    }
}
