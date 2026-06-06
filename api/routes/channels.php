<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
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

    $conversation->loadMissing('activity');
    $activity = $conversation->activity;

    if (! $activity) {
        return false;
    }

    if ((string) $activity->manager_id === (string) $user->id) {
        return true;
    }

    return $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_MESSAGES);
});

Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (string) $user->id === $userId;
});
