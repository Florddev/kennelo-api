<?php

declare(strict_types=1);

use App\Enums\ActivityStatusEnum;
use App\Enums\AdminActionTypeEnum;
use App\Models\Activity;
use App\Models\AdminAction;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

it('lists activities filtered by status', function () {
    $pending = Activity::factory()->create();
    Activity::factory()->approved()->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/activities?status=pending')
        ->assertOk()
        ->assertJsonPath('data.*.id', [$pending->id])
        ->assertJsonPath('data.0.status', 'pending');
});

it('approves an activity whose profession requires no document, and logs it', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    $action = AdminAction::query()->where('action', AdminActionTypeEnum::APPROVE_ACTIVITY)->sole();

    expect($activity->fresh()->reviewed_by)->toBe($admin->id)
        ->and($action->target_user_id)->toBe($activity->organization->owner_id)
        ->and($action->metadata)->toBe(['activity_id' => $activity->id]);
});

it('rejects or suspends an activity with a reason sent to its team', function (string $action, ActivityStatusEnum $status) {
    Notification::fake();
    $activity = Activity::factory()->approved()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/activities/{$activity->id}/{$action}", ['reason' => 'Photos trompeuses'])
        ->assertOk()
        ->assertJsonPath('data.status', $status->value)
        ->assertJsonPath('data.rejection_reason', 'Photos trompeuses');

    Notification::assertSentTo($activity->organization->owner, AppNotification::class);
})->with([
    ['reject', ActivityStatusEnum::REJECTED],
    ['suspend', ActivityStatusEnum::SUSPENDED],
]);

it('makes a suspended activity disappear from the search', function () {
    $activity = Activity::factory()->bookable()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/activities/{$activity->id}/suspend", ['reason' => 'Fraude signalée'])
        ->assertOk();

    $this->getJson('/api/explore/search')->assertJsonCount(0, 'data');
});

it('forbids the review routes to a professional', function () {
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($activity->organization->owner))
        ->postJson("/api/admin/activities/{$activity->id}/approve")
        ->assertForbidden();

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson('/api/admin/activity-documents')
        ->assertForbidden();
});
