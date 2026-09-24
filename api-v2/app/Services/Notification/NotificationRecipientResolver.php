<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Détermine qui reçoit une notification. Les destinataires liés à une activité ou à une conversation
 * (équipe de l'entreprise) arrivent avec le lot « Entreprise et équipe ».
 */
class NotificationRecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::role('admin')->get();
    }
}
