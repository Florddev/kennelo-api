<?php

declare(strict_types=1);

use App\Enums\EstablishmentPermissionEnum;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth.jwt']]);

Broadcast::channel('conversation.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    if ((string) $conversation->user_id === (string) $user->id) {
        return true;
    }

    $conversation->loadMissing('establishment');
    $establishment = $conversation->establishment;

    if (! $establishment) {
        return false;
    }

    if ((string) $establishment->manager_id === (string) $user->id) {
        return true;
    }

    return $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_MESSAGES);
});

Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (string) $user->id === $userId;
});
