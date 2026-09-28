<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageSenderTypeEnum;
use App\Enums\MessageTypeEnum;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut, un message texte du client de la conversation. La conversation prend la date du message comme
 * date du dernier message.
 *
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => fn (array $attributes): ?string => Conversation::query()->whereKey($attributes['conversation_id'])->value('user_id'),
            'sender_type' => MessageSenderTypeEnum::USER,
            'message_type' => MessageTypeEnum::TEXT,
            'content' => fake()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Message $message): void {
            Conversation::query()->whereKey($message->conversation_id)->update(['last_message_at' => $message->created_at]);
        });
    }

    /**
     * Écrit par un membre de l'équipe de l'activité.
     */
    public function fromTeam(string $memberId): static
    {
        return $this->state(fn (): array => ['sender_id' => $memberId, 'sender_type' => MessageSenderTypeEnum::ACTIVITY]);
    }
}
