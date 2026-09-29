<?php

declare(strict_types=1);

use App\Broadcasting\ConversationChannel;
use App\Enums\ActivityStatusEnum;
use App\Enums\MessageSenderTypeEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\OrganizationRoleEnum;
use App\Events\Conversation\MessagesRead;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('contacting an activity', function () {
    it('opens the conversation of a client with a bookable activity, once', function () {
        $activity = Activity::factory()->bookable()->create();
        $client = User::factory()->create();

        $first = $this->withHeaders(asUser($client))
            ->postJson("/api/activities/{$activity->id}/conversations")
            ->assertCreated()
            ->assertJsonPath('client_id', $client->id)
            ->assertJsonPath('activity.name', $activity->name)
            ->assertJsonPath('unread_count', 0);
        $this->postJson("/api/activities/{$activity->id}/conversations")->assertOk()->assertJsonPath('id', $first->json('id'));

        expect(Conversation::query()->count())->toBe(1);

        // Sans message, elle n'apparaît pas encore dans la liste.
        $this->getJson('/api/conversations')->assertOk()->assertJsonCount(0, 'data');
    });

    it('cannot contact an activity that is not bookable, or its own', function () {
        $hidden = Activity::factory()->bookable()->create();
        $hidden->forceFill(['status' => ActivityStatusEnum::PENDING])->save();
        $activity = Activity::factory()->bookable()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/activities/{$hidden->id}/conversations")
            ->assertNotFound();
        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::EMPLOYEE, $activity->id)))
            ->postJson("/api/activities/{$activity->id}/conversations")
            ->assertForbidden()
            ->assertJsonPath('message', __('conversations.errors.own_activity'));
    });
});

describe('listing', function () {
    it('lists the conversations of the client, the most recent first, with their unread messages', function () {
        $client = User::factory()->create();
        $older = Conversation::factory()->for($client)->create();
        $recent = Conversation::factory()->for($client)->create();
        Message::factory()->for($older)->create(['created_at' => now()->subDay()]);
        $owner = $recent->activity->organization->owner;
        Message::factory()->for($recent)->fromTeam($owner->id)->count(2)->create();
        Message::factory()->for(Conversation::factory())->create();

        $this->withHeaders(asUser($client))
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$recent->id, $older->id])
            ->assertJsonPath('data.0.unread_count', 2)
            ->assertJsonPath('data.0.last_message.sender_type', 'activity')
            ->assertJsonPath('data.0.last_message.sender.id', $owner->id)
            ->assertJsonPath('data.1.unread_count', 0);
    });

    it('gives the inbox of an activity to the members who reply to its clients', function () {
        $conversation = Conversation::factory()->create();
        Message::factory()->for($conversation)->create();
        $activity = $conversation->activity;

        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::EMPLOYEE, $activity->id)))
            ->getJson("/api/activities/{$activity->id}/conversations")
            ->assertOk()
            ->assertJsonPath('data.0.id', $conversation->id)
            ->assertJsonPath('data.0.client.id', $conversation->user_id)
            ->assertJsonPath('data.0.unread_count', 1);
        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->getJson("/api/activities/{$activity->id}/conversations")
            ->assertForbidden();
        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/activities/{$activity->id}/conversations")
            ->assertNotFound();
    });

    it('shows a conversation to its client and to the team, not to others', function () {
        $conversation = Conversation::factory()->create();
        $organization = $conversation->activity->organization;

        $this->withHeaders(asUser($conversation->user))->getJson("/api/conversations/{$conversation->id}")->assertOk();
        $this->withHeaders(asUser($organization->owner))->getJson("/api/conversations/{$conversation->id}")->assertOk();
        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))->getJson("/api/conversations/{$conversation->id}")->assertForbidden();
        $this->withHeaders(asUser(User::factory()->create()))->getJson("/api/conversations/{$conversation->id}")->assertNotFound();
    });
});

describe('conversation of a booking', function () {
    it('opens the conversation of a booking, with its thread, for the client and for the team', function () {
        $booking = Booking::factory()->create();

        $conversation = $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/conversation")
            ->assertCreated()
            ->assertJsonPath('bookings.0.id', $booking->id)
            ->assertJsonPath('bookings.0.is_active', true)
            ->json('id');

        $this->withHeaders(asUser(memberOf($booking->organization, OrganizationRoleEnum::EMPLOYEE, $booking->activity_id)))
            ->postJson("/api/bookings/{$booking->id}/conversation")
            ->assertOk()
            ->assertJsonPath('id', $conversation);

        expect(BookingThread::query()->sole()->conversation_id)->toBe($conversation);
    });

    it('keeps the conversation of a booking from the others', function () {
        $booking = Booking::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))->postJson("/api/bookings/{$booking->id}/conversation")->assertNotFound();
        $this->withHeaders(asUser(memberOf($booking->organization, OrganizationRoleEnum::ACCOUNTANT)))->postJson("/api/bookings/{$booking->id}/conversation")->assertForbidden();
    });
});

describe('unread messages', function () {
    it('counts what the other side wrote, for the client and for each member', function () {
        $conversation = Conversation::factory()->create();
        $activity = $conversation->activity;
        $employee = memberOf($activity->organization, OrganizationRoleEnum::EMPLOYEE, $activity->id);
        Message::factory()->for($conversation)->count(2)->create();
        Message::factory()->for($conversation)->fromTeam($employee->id)->create();
        Message::factory()->for($conversation)->create(['sender_id' => null, 'sender_type' => MessageSenderTypeEnum::SYSTEM, 'message_type' => MessageTypeEnum::SYSTEM, 'content' => 'booking_created']);

        $this->withHeaders(asUser($conversation->user))->getJson('/api/conversations/unread-count')->assertOk()->assertJsonPath('unread_count', 1);
        $this->withHeaders(asUser($employee))->getJson('/api/conversations/unread-count')->assertJsonPath('unread_count', 2);
        $this->withHeaders(asUser($activity->organization->owner))->getJson('/api/conversations/unread-count')->assertJsonPath('unread_count', 2);
        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::ACCOUNTANT)))->getJson('/api/conversations/unread-count')->assertJsonPath('unread_count', 0);
    });

    it('marks the messages of the other side as read, for the reader only', function () {
        Event::fake([MessagesRead::class]);
        $conversation = Conversation::factory()->create();
        $owner = $conversation->activity->organization->owner;
        $employee = memberOf($conversation->activity->organization, OrganizationRoleEnum::EMPLOYEE, $conversation->activity_id);
        Message::factory()->for($conversation)->count(2)->create();
        $reply = Message::factory()->for($conversation)->fromTeam($owner->id)->create();

        $this->withHeaders(asUser($owner))->putJson("/api/conversations/{$conversation->id}/read")->assertOk()->assertJsonPath('marked_count', 2);
        $this->putJson("/api/conversations/{$conversation->id}/read")->assertJsonPath('marked_count', 0);

        Event::assertDispatched(MessagesRead::class, fn (MessagesRead $event): bool => $event->reader->is($owner) && $event->count === 2);
        $this->withHeaders(asUser($employee))->getJson('/api/conversations/unread-count')->assertJsonPath('unread_count', 2);

        // Le client voit ses messages lus ; la réponse de l'équipe ne l'est pas encore.
        $this->withHeaders(asUser($conversation->user))
            ->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertJsonPath('data.0.id', $reply->id)
            ->assertJsonPath('data.*.is_read', [false, true, true]);
    });
});

it('lets into the channel of a conversation the people who can read it', function () {
    $conversation = Conversation::factory()->create();
    $organization = $conversation->activity->organization;
    $channel = new ConversationChannel;

    expect($channel->join($conversation->user, $conversation))->toBeTrue()
        ->and($channel->join($organization->owner, $conversation))->toBeTrue()
        ->and($channel->join(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT), $conversation))->toBeFalse()
        ->and($channel->join(User::factory()->create(), $conversation))->toBeFalse();

    $this->postJson('/api/broadcasting/auth', ['channel_name' => "private-conversation.{$conversation->id}"])->assertUnauthorized();
});
