<?php

declare(strict_types=1);

use App\Enums\SenderTypeEnum;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\NewMessageNotification;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;

it('user can list messages in their conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    Message::factory()->count(5)->create(['conversation_id' => $conversation->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}/messages")
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('user cannot list messages in another user conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}/messages")
        ->assertForbidden();
});

it('messages are paginated', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    Message::factory()->count(10)->create(['conversation_id' => $conversation->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}/messages?per_page=5")
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('messages can be filtered by booking_id', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $booking = Booking::factory()->create(['user_id' => $user->id]);

    Message::factory()->count(2)->create([
        'conversation_id' => $conversation->id,
        'booking_id' => $booking->id,
    ]);
    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'booking_id' => null,
    ]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}/messages?booking_id={$booking->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('user can send a text message', function () {
    Event::fake([MessageSent::class, NewMessageNotification::class]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Hello!',
        ])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Hello!')
        ->assertJsonPath('data.sender_type', SenderTypeEnum::USER->value);

    Event::assertDispatched(MessageSent::class);
    Event::assertDispatched(NewMessageNotification::class);
});

it('user cannot send message in another user conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Hello!',
        ])
        ->assertForbidden();
});

it('validates content is required', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

it('validates content max length', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => str_repeat('a', 5001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

it('user can mark messages as read', function () {
    Event::fake([MessagesRead::class]);

    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $other->id,
    ]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/conversations/{$conversation->id}/messages/read")
        ->assertOk()
        ->assertJsonPath('data.marked_count', 3);

    Event::assertDispatched(MessagesRead::class);
});

it('marking already read messages returns zero', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    Message::factory()->count(2)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user->id,
    ]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/conversations/{$conversation->id}/messages/read")
        ->assertOk()
        ->assertJsonPath('data.marked_count', 0);
});

it('unauthenticated user cannot send messages', function () {
    $conversation = Conversation::factory()->create();

    $this->postJson("/api/conversations/{$conversation->id}/messages", [
        'content' => 'Hello!',
    ])->assertUnauthorized();
});

it('sending a message updates conversation last_message_at', function () {
    Event::fake([MessageSent::class, NewMessageNotification::class]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'last_message_at' => now()->subDay(),
    ]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Update timestamp!',
        ])
        ->assertCreated();

    $conversation->refresh();
    /** @var Carbon $lastMessageAt */
    $lastMessageAt = $conversation->last_message_at;
    expect($lastMessageAt->isToday())->toBeTrue();
});
