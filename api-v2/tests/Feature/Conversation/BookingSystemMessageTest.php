<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\MessageTypeEnum;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCompleted;
use App\Events\Booking\BookingConfirmed;
use App\Events\Booking\BookingCreated;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

/**
 * @return list<string>
 */
function systemEvents(Booking $booking): array
{
    return Message::query()
        ->where('booking_id', $booking->id)
        ->where('message_type', MessageTypeEnum::SYSTEM)
        ->orderBy('created_at')
        ->orderBy('id')
        ->pluck('content')
        ->all();
}

it('opens the conversation of a new booking, with its thread and a system message', function () {
    $booking = Booking::factory()->create();

    BookingCreated::dispatch($booking);

    $conversation = Conversation::query()->sole();
    expect([$conversation->user_id, $conversation->activity_id])->toBe([$booking->user_id, $booking->activity_id])
        ->and(BookingThread::query()->sole()->conversation_id)->toBe($conversation->id)
        ->and(systemEvents($booking))->toBe(['booking_created'])
        ->and($conversation->last_message_at)->not->toBeNull();

    $this->withHeaders(asUser($booking->user))
        ->getJson("/api/conversations/{$conversation->id}/messages")
        ->assertJsonPath('data.0.type', 'system')
        ->assertJsonPath('data.0.system_event', 'booking_created')
        ->assertJsonPath('data.0.sender', null)
        ->assertJsonPath('data.0.content', __('conversations.system.booking_created'));
});

it('follows the booking to its end, then archives its thread', function () {
    $booking = Booking::factory()->confirmed()->create();
    BookingCreated::dispatch($booking);
    BookingConfirmed::dispatch($booking);

    expect(BookingThread::query()->sole()->isActive())->toBeTrue();

    $booking->forceFill(['status' => BookingStatusEnum::COMPLETED])->save();
    BookingCompleted::dispatch($booking);

    expect(systemEvents($booking))->toBe(['booking_created', 'booking_confirmed', 'booking_completed'])
        ->and(BookingThread::query()->sole()->isActive())->toBeFalse();
});

it('says who cancelled', function () {
    $booking = Booking::factory()->confirmed()->create();
    $booking->forceFill(['status' => BookingStatusEnum::CANCELLED, 'cancelled_by_role' => CancelledByRoleEnum::PRO])->save();

    BookingCancelled::dispatch($booking);

    expect(systemEvents($booking))->toBe(['booking_cancelled_by_pro'])
        ->and(BookingThread::query()->sole()->archived_at)->not->toBeNull();
});

it('posts an event only once when it is delivered again', function () {
    $booking = Booking::factory()->create();

    BookingCreated::dispatch($booking);
    BookingCreated::dispatch($booking);

    expect(systemEvents($booking))->toBe(['booking_created'])
        ->and(Conversation::query()->count())->toBe(1);
});

it('opens the conversation when a client books through the API', function () {
    $unitType = dogBoarding();
    $client = User::factory()->create();
    stripeAuthorizes($this->stripe());

    $this->withHeaders(asUser($client))
        ->postJson('/api/bookings', stayRequest($unitType, [dogOf($client, $unitType)]))
        ->assertCreated();

    expect(Conversation::query()->sole()->user_id)->toBe($client->id)
        ->and(Message::query()->sole()->content)->toBe('booking_created');
});
