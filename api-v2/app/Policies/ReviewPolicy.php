<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Un avis publié se lit par tous. Avant sa publication, il n'est lu que par son auteur et par Kennelo : la
 * partie notée ne le découvre qu'une fois le sien donné, ou le délai passé.
 *
 * Le client note l'activité ; l'équipe (bookings.manage) note le client. La partie notée répond, et lit le
 * retour privé : le client pour un avis sur lui, l'équipe (bookings.view) pour un avis sur l'activité.
 */
class ReviewPolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    public function view(User $user, Review $review): Response
    {
        return $review->is_published || $review->reviewer_id === $user->id || $user->hasRole('admin')
            ? Response::allow()
            : Response::denyAsNotFound(__('errors.not_found'));
    }

    public function create(User $user, Booking $booking): Response
    {
        return $booking->user_id === $user->id
            ? Response::allow()
            : $this->team($user, $booking->activity, OrganizationPermissionEnum::BOOKINGS_MANAGE);
    }

    public function respond(User $user, Review $review): Response
    {
        if (! $review->is_published) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $review->reviewer_type === ReviewerTypeEnum::USER
            ? $this->team($user, $review->activity, OrganizationPermissionEnum::BOOKINGS_MANAGE)
            : ($review->booking?->user_id === $user->id ? Response::allow() : Response::denyAsNotFound(__('errors.not_found')));
    }

    /**
     * N'importe qui signale un avis publié, sauf son auteur.
     */
    public function report(User $user, Review $review): Response
    {
        if (! $review->is_published) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $review->reviewer_id === $user->id ? Response::deny(__('reviews.errors.own_review')) : Response::allow();
    }

    /**
     * Le retour privé : pour la partie notée et pour Kennelo. Lu à l'affichage, il ne refuse rien.
     */
    public function viewPrivateFeedback(User $user, Review $review): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($review->reviewer_type === ReviewerTypeEnum::ACTIVITY) {
            return $review->booking?->user_id === $user->id;
        }

        $organization = $review->activity?->organization;

        return $organization !== null && $this->permissions->allows($user, OrganizationPermissionEnum::BOOKINGS_VIEW, $organization, $review->activity_id);
    }

    private function team(User $user, ?Activity $activity, OrganizationPermissionEnum $permission): Response
    {
        $organization = $activity?->organization;

        if ($activity === null || $organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, $permission, $organization, $activity->id) ? Response::allow() : Response::deny();
    }
}
