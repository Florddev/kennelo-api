<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Models\Booking;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Une réservation appartient à son client et à l'équipe de l'activité : les autres reçoivent une 404.
 * Dans l'équipe, bookings.view permet de la voir, bookings.manage d'y répondre et de l'ajuster.
 */
class BookingPolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    public function view(User $user, Booking $booking): Response
    {
        if ($booking->user_id === $user->id) {
            return Response::allow();
        }

        return $this->team($user, $booking, OrganizationPermissionEnum::BOOKINGS_VIEW);
    }

    /**
     * Annuler en tant que client, confirmer un paiement complémentaire.
     */
    public function act(User $user, Booking $booking): Response
    {
        return $booking->user_id === $user->id ? Response::allow() : Response::denyAsNotFound(__('errors.not_found'));
    }

    /**
     * Accepter, refuser, annuler en tant que pro, ajouter ou retirer une option, lire le journal financier.
     */
    public function manage(User $user, Booking $booking): Response
    {
        return $this->team($user, $booking, OrganizationPermissionEnum::BOOKINGS_MANAGE);
    }

    private function team(User $user, Booking $booking, OrganizationPermissionEnum $permission): Response
    {
        $organization = $booking->organization;

        if ($organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, $permission, $organization, $booking->activity_id)
            ? Response::allow()
            : Response::deny();
    }
}
