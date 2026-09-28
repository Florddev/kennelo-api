<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Canal privé d'une conversation : les mêmes personnes que celles qui peuvent la lire par l'API.
 */
class ConversationChannel
{
    public function join(User $user, Conversation $conversation): bool
    {
        return Gate::forUser($user)->allows('view', $conversation);
    }
}
