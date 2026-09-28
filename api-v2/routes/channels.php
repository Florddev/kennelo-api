<?php

declare(strict_types=1);

use App\Broadcasting\ConversationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canaux privés du temps réel. L'autorisation passe par POST /api/broadcasting/auth, derrière la session Sanctum.

// Notifications d'une personne (NotificationCreated).
Broadcast::channel('user.{id}', fn (User $user, string $id): bool => $user->id === $id);

// Messages d'une conversation (MessageSent, MessagesRead).
Broadcast::channel('conversation.{conversation}', ConversationChannel::class);
