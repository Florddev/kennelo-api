<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\SenderTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

// ─── index ────────────────────────────────────────────────────────────────────

it('manager can list activity bookings', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    Booking::factory()->count(3)->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/bookings")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('non-manager cannot list activity bookings', function () {
    $manager = User::factory()->create();
    $other = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($other))
        ->getJson("/api/activities/{$activity->id}/bookings")
        ->assertForbidden();
});

it('unauthenticated user cannot list activity bookings', function () {
    $activity = Activity::factory()->create();

    $this->getJson("/api/activities/{$activity->id}/bookings")
        ->assertUnauthorized();
});

// ─── confirm ──────────────────────────────────────────────────────────────────

it('manager can confirm a pending booking', function () {
    fakeStripe();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::CONFIRMED->value);
});

it('confirming a booking captures the payment', function () {
    fakeStripe();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'activity_id' => $activity->id,
        'stripe_payment_intent_id' => 'pi_test_capture',
        'payment_status' => 'requires_capture',
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/confirm")
        ->assertOk();

    $booking->refresh();

    expect($booking->payment_status->value)->toBe('succeeded');
    expect($booking->paid_at)->not->toBeNull();
});

it('rejecting a booking releases the authorization', function () {
    fakeStripe();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'activity_id' => $activity->id,
        'stripe_payment_intent_id' => 'pi_test_release',
        'payment_status' => 'requires_capture',
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::REJECTED->value);

    expect($booking->fresh()->payment_status->value)->toBe('canceled');
});

it('manager cannot confirm an already confirmed booking', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->confirmed()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/confirm")
        ->assertUnprocessable();
});

// ─── cancel ───────────────────────────────────────────────────────────────────

it('manager can cancel a pending booking', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::REJECTED->value);
});

// ─── complete ─────────────────────────────────────────────────────────────────

it('manager can complete a confirmed booking', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->confirmed()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::COMPLETED->value);
});

it('manager cannot complete a pending booking', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/complete")
        ->assertUnprocessable();
});

it('confirming a booking sends a booking reference message from the activity', function () {
    Event::fake();
    fakeStripe();

    $manager = User::factory()->create();
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
    ]);

    $conversation = Conversation::create(['user_id' => $user->id, 'activity_id' => $activity->id]);
    BookingThread::create(['booking_id' => $booking->id, 'conversation_id' => $conversation->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/confirm")
        ->assertOk();

    expect(
        Message::where('booking_id', $booking->id)
            ->where('message_type', MessageTypeEnum::BOOKING_REFERENCE->value)
            ->where('sender_type', SenderTypeEnum::ACTIVITY->value)
            ->exists()
    )->toBeTrue();
});

it('cancelling a booking sends a booking reference message from the activity', function () {
    Event::fake();

    $manager = User::factory()->create();
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
    ]);

    $conversation = Conversation::create(['user_id' => $user->id, 'activity_id' => $activity->id]);
    BookingThread::create(['booking_id' => $booking->id, 'conversation_id' => $conversation->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/cancel")
        ->assertOk();

    expect(
        Message::where('booking_id', $booking->id)
            ->where('message_type', MessageTypeEnum::BOOKING_REFERENCE->value)
            ->where('sender_type', SenderTypeEnum::ACTIVITY->value)
            ->exists()
    )->toBeTrue();
});

it('manager cannot act on a booking from another activity', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $otherActivity = Activity::factory()->create();
    $booking = Booking::factory()->pending()->create(['activity_id' => $otherActivity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/bookings/{$booking->id}/confirm")
        ->assertNotFound();
});
