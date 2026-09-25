<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Access\Response;

/**
 * Le carnet d'adresses n'appartient qu'à son client : pour toute autre personne, l'adresse n'existe pas.
 */
class UserAddressPolicy
{
    public function update(User $user, UserAddress $address): Response
    {
        return $this->owner($user, $address);
    }

    public function delete(User $user, UserAddress $address): Response
    {
        return $this->owner($user, $address);
    }

    private function owner(User $user, UserAddress $address): Response
    {
        return $address->user_id === $user->id ? Response::allow() : Response::denyAsNotFound(__('errors.not_found'));
    }
}
