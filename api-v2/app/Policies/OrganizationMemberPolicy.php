<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class OrganizationMemberPolicy
{
    /**
     * Seule la personne invitée répond à son invitation ; pour toute autre, elle n'existe pas.
     * L'invitation d'une entreprise fermée n'existe plus non plus.
     */
    public function respond(User $user, OrganizationMember $member): Response
    {
        return $member->user_id === $user->id && $member->organization !== null
            ? Response::allow()
            : Response::denyAsNotFound(__('errors.not_found'));
    }

    /**
     * Un membre peut toujours quitter l'entreprise ; retirer quelqu'un d'autre demande de gérer l'équipe.
     */
    public function delete(User $user, OrganizationMember $member): Response
    {
        if ($member->user_id === $user->id) {
            return Response::allow();
        }

        return Gate::forUser($user)->inspect('manageTeam', $member->organization);
    }
}
