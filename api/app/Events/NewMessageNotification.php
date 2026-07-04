<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ActivityPermissionEnum;
use App\Models\ActivityCollaborator;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageNotification implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $this->message->loadMissing('conversation.activity');
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
            $collaboratorIds = ActivityCollaborator::query()
                ->where('activity_id', $activity->id)
                ->accepted()
                ->withPermission(ActivityPermissionEnum::MANAGE_MESSAGES)
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
