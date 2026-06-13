<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\NotificationCreated;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\Channels\UserDatabaseChannel;
use App\Services\Booking\BookingService;
use App\Services\Favorite\FavoriteService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;

// ─── custom channel ───────────────────────────────────────────────────────────

it('persists a row and broadcasts when the channel sends a notification', function () {
    Event::fake([NotificationCreated::class]);

    $user = User::factory()->create();
    $channel = app(UserDatabaseChannel::class);

    $channel->send($user, new AppNotification(NotificationTypeEnum::BOOKING_CONFIRMED, ['booking_id' => 'abc']));

    $record = Notification::where('user_id', $user->id)->first();

    expect($record)->not->toBeNull()
        ->and($record->type)->toBe(NotificationTypeEnum::BOOKING_CONFIRMED)
        ->and($record->data)->toBe(['booking_id' => 'abc']);

    Event::assertDispatched(NotificationCreated::class, fn (NotificationCreated $e): bool => (string) $e->notification->user_id === (string) $user->id);
});

it('routes the broadcast to the recipient private channel', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $event = new NotificationCreated($notification);
    $channels = $event->broadcastOn();

    expect($channels[0]->name)->toBe('private-user.'.$user->id)
        ->and($event->broadcastAs())->toBe('notification.created');
});

// ─── dispatch wiring ──────────────────────────────────────────────────────────

it('notifies the client when a booking is confirmed', function () {
    NotificationFacade::fake();

    $client = User::factory()->create();
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->create([
        'user_id' => $client->id,
        'activity_id' => $activity->id,
        'status' => BookingStatusEnum::PENDING,
    ]);

    app(BookingService::class)->confirm($booking, $manager);

    NotificationFacade::assertSentTo(
        $client,
        AppNotification::class,
    );
});

it('skips tier3 notifications when the feature is disabled', function () {
    config()->set('notifications.tier3_enabled', false);
    NotificationFacade::fake();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $user = User::factory()->create();

    app(FavoriteService::class)->add($user, $activity);

    NotificationFacade::assertNothingSent();
});

it('sends tier3 notifications when the feature is enabled', function () {
    config()->set('notifications.tier3_enabled', true);
    NotificationFacade::fake();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $user = User::factory()->create();

    app(FavoriteService::class)->add($user, $activity);

    NotificationFacade::assertSentTo($manager, AppNotification::class);
});
