<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Une conversation se lit et s'écrit par son client, et côté pro par les membres qui ont messages.reply sur
 * l'activité. Les autres reçoivent une 404 ; un membre sans ce droit, une 403.
 */
class ConversationPolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    public function view(User $user, Conversation $conversation): Response
    {
        return $conversation->isClient($user) ? Response::allow() : $this->team($user, $conversation->activity);
    }

    /**
     * Boîte de réception de l'équipe d'une activité.
     */
    public function viewForActivity(User $user, Activity $activity): Response
    {
        return $this->team($user, $activity);
    }

    /**
     * Un client contacte une activité réservable, avant ou sans réservation. L'équipe, elle, écrit à un client
     * depuis une de ses réservations.
     */
    public function contact(User $user, Activity $activity): Response
    {
        if (! Activity::query()->whereKey($activity->id)->bookable()->exists()) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $activity->organization !== null && $this->permissions->isMember($user, $activity->organization)
            ? Response::deny(__('conversations.errors.own_activity'))
            : Response::allow();
    }

    /**
     * Ouvrir la conversation d'une réservation : son client, ou l'équipe (messages.reply).
     */
    public function openForBooking(User $user, Booking $booking): Response
    {
        return $booking->user_id === $user->id ? Response::allow() : $this->team($user, $booking->activity);
    }

    private function team(User $user, ?Activity $activity): Response
    {
        $organization = $activity?->organization;

        if ($activity === null || $organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, OrganizationPermissionEnum::MESSAGES_REPLY, $organization, $activity->id)
            ? Response::allow()
            : Response::deny();
    }
}
