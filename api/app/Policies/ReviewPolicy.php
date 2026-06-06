<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BookingStatusEnum;
use App\Enums\EstablishmentPermissionEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function view(User $user, Review $review): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($review->is_published) {
            return true;
        }

        if ((string) $review->reviewer_id === (string) $user->id) {
            return true;
        }

        $review->loadMissing('booking.establishment');
        $booking = $review->booking;

        if ($booking === null) {
            return false;
        }

        if ((string) $booking->user_id === (string) $user->id) {
            return true;
        }

        $establishment = $booking->establishment;

        if ($establishment === null) {
            return false;
        }

        if ((string) $establishment->manager_id === (string) $user->id) {
            return true;
        }

        return $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_BOOKINGS);
    }

    public function create(User $user, Booking $booking): bool
    {
        if ($booking->status !== BookingStatusEnum::COMPLETED) {
            return false;
        }

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

    public function respond(User $user, Review $review): bool
    {
        $review->loadMissing('booking.establishment');
        $booking = $review->booking;

        if ($booking === null) {
            return false;
        }

        if ($review->reviewer_type === ReviewerTypeEnum::USER) {
            $establishment = $booking->establishment;

            if ($establishment === null) {
                return false;
            }

            if ((string) $establishment->manager_id === (string) $user->id) {
                return true;
            }

            return $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_BOOKINGS);
        }

        return (string) $booking->user_id === (string) $user->id;
    }

    public function report(User $user, Review $review): bool
    {
        if (! $review->is_published) {
            return false;
        }

        return (string) $review->reviewer_id !== (string) $user->id;
    }
}
