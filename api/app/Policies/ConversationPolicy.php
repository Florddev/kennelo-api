<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EstablishmentPermission;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if ((string) $conversation->user_id === (string) $user->id) {
            return true;
        }

        $conversation->loadMissing('establishment');
        $establishment = $conversation->establishment;

        if ($establishment === null) {
            return false;
        }

        if ((string) $establishment->manager_id === (string) $user->id) {
            return true;
        }

        return $establishment->collaboratorHasPermission($user, EstablishmentPermission::MANAGE_MESSAGES);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    public function manageForEstablishment(User $user, Establishment $establishment): bool
    {
        if ((string) $establishment->manager_id === (string) $user->id) {
            return true;
        }

        return $establishment->collaboratorHasPermission($user, EstablishmentPermission::MANAGE_MESSAGES);
    }
}
