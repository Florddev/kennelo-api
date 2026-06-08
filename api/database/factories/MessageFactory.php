<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageTypeEnum;
use App\Enums\SenderTypeEnum;
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
            'sender_type' => SenderTypeEnum::USER,
            'message_type' => MessageTypeEnum::TEXT,
            'content' => fake()->sentence(),
        ];
    }

    public function fromActivity(): static
    {
        return $this->state(['sender_type' => SenderTypeEnum::ACTIVITY]);
    }

    public function system(): static
    {
        return $this->state([
            'sender_type' => SenderTypeEnum::SYSTEM,
            'message_type' => MessageTypeEnum::SYSTEM,
            'sender_id' => null,
        ]);
    }
}
