<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageType;
use App\Enums\SenderType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'sender_type' => SenderType::User,
            'message_type' => MessageType::Text,
            'content' => fake()->sentence(),
        ];
    }

    public function fromEstablishment(): static
    {
        return $this->state(['sender_type' => SenderType::Establishment]);
    }

    public function system(): static
    {
        return $this->state([
            'sender_type' => SenderType::System,
            'message_type' => MessageType::System,
            'sender_id' => null,
        ]);
    }
}
