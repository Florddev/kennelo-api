<?php

declare(strict_types=1);

use App\Enums\CollaboratorStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

it('notifies the invited user when a collaborator is invited', function () {
    NotificationFacade::fake();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $invitee = User::factory()->create();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => $invitee->email])
        ->assertCreated();

    NotificationFacade::assertSentTo(
        $invitee,
        AppNotification::class,
        fn (AppNotification $notification): bool => $notification->toUserDatabase($invitee)['type'] === NotificationTypeEnum::COLLABORATOR_INVITED->value
    );
});

it('notifies the manager when an invitation is accepted', function () {
    NotificationFacade::fake();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $invitee = User::factory()->create();
    attachCollaborator($activity, $invitee, [], CollaboratorStatusEnum::PENDING);

    $this->withHeaders(asUser($invitee))
        ->putJson("/api/activities/{$activity->id}/collaborators/accept")
        ->assertOk();

    NotificationFacade::assertSentTo(
        $manager,
        AppNotification::class,
        fn (AppNotification $notification): bool => $notification->toUserDatabase($manager)['type'] === NotificationTypeEnum::COLLABORATOR_ACCEPTED->value
    );
});

it('notifies the manager when an invitation is declined', function () {
    NotificationFacade::fake();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $invitee = User::factory()->create();
    attachCollaborator($activity, $invitee, [], CollaboratorStatusEnum::PENDING);

    $this->withHeaders(asUser($invitee))
        ->putJson("/api/activities/{$activity->id}/collaborators/decline")
        ->assertOk();

    NotificationFacade::assertSentTo(
        $manager,
        AppNotification::class,
        fn (AppNotification $notification): bool => $notification->toUserDatabase($manager)['type'] === NotificationTypeEnum::COLLABORATOR_DECLINED->value
    );
});
