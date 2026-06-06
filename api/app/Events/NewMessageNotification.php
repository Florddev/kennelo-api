<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ActivityPermissionEnum;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $this->message->loadMissing('conversation.activity.collaboratorPermissions');
        $conversation = $this->message->conversation;
        $senderId = (string) $this->message->sender_id;
        $channels = [];

        if ($senderId !== (string) $conversation->user_id) {
            $channels[] = new PrivateChannel('user.'.$conversation->user_id);
        }

        $activity = $conversation->activity;

        if ($activity && $senderId !== (string) $activity->manager_id) {
            $channels[] = new PrivateChannel('user.'.$activity->manager_id);
        }

        if ($activity) {
            $collaboratorIds = $activity->collaboratorPermissions
                ->where('permission', ActivityPermissionEnum::MANAGE_MESSAGES->value)
                ->pluck('user_id')
                ->unique()
                ->reject(fn (string $id) => $id === $senderId);

            foreach ($collaboratorIds as $collaboratorId) {
                $channels[] = new PrivateChannel('user.'.$collaboratorId);
            }
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'content_preview' => str($this->message->content)->limit(100)->toString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.message';
    }
}
