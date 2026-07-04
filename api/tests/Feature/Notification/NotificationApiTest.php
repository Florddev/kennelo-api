<?php

declare(strict_types=1);

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Cache;

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list their notifications', function () {
    $user = User::factory()->create();
    Notification::factory()->count(3)->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('user only sees their own notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Notification::factory()->count(2)->create(['user_id' => $user->id]);
    Notification::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can filter notifications to unread only', function () {
    $user = User::factory()->create();
    Notification::factory()->count(2)->create(['user_id' => $user->id]);
    Notification::factory()->read()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications?unread_only=1')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('unauthenticated user cannot list notifications', function () {
    $this->getJson('/api/notifications')
        ->assertUnauthorized();
});

// ─── unread-count ───────────────────────────────────────────────────────────────

it('returns the unread notifications count', function () {
    $user = User::factory()->create();
    Notification::factory()->count(2)->create(['user_id' => $user->id]);
    Notification::factory()->read()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 2);
});

// ─── mark as read ─────────────────────────────────────────────────────────────

it('can mark a notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('is_read', true);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('cannot mark another user notification as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/notifications/{$notification->id}/read")
        ->assertForbidden();
});

it('can mark all notifications as read', function () {
    $user = User::factory()->create();
    Notification::factory()->count(3)->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->putJson('/api/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('data.marked_count', 3);

    expect(Notification::where('user_id', $user->id)->whereNull('read_at')->count())->toBe(0);
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('can delete its own notification', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/notifications/{$notification->id}")
        ->assertOk();

    expect(Notification::find($notification->id))->toBeNull();
});

it('cannot delete another user notification', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/notifications/{$notification->id}")
        ->assertForbidden();

    expect(Notification::find($notification->id))->not->toBeNull();
});

// ─── unread-count cache invalidation ────────────────────────────────────────────

it('refreshes the unread count cache when a notification is deleted', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 1);

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/notifications/{$notification->id}")
        ->assertOk();

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 0);
});

it('refreshes the unread count cache when a new notification is dispatched', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 0);

    app(NotificationService::class)->notify($user, NotificationTypeEnum::BOOKING_CONFIRMED, ['booking_id' => 'abc']);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 1);
});

it('caches the unread count between calls without a mutation', function () {
    $user = User::factory()->create();
    Notification::factory()->count(2)->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 2);

    Notification::where('user_id', $user->id)->update(['read_at' => now()]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 2);

    Cache::forget("notifications:unread_count:{$user->id}");

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications/unread-count')
        ->assertJsonPath('data.unread_count', 0);
});

it('exposes the notification type and data payload', function () {
    $user = User::factory()->create();
    Notification::factory()
        ->ofType(NotificationTypeEnum::BOOKING_CONFIRMED)
        ->create(['user_id' => $user->id, 'data' => ['booking_id' => 'abc']]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.type', NotificationTypeEnum::BOOKING_CONFIRMED->value)
        ->assertJsonPath('data.0.data.booking_id', 'abc');
});
