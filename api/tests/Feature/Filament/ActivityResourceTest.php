<?php

declare(strict_types=1);

use App\Enums\ActivityStatusEnum;
use App\Enums\UserStatusEnum;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Activity;
use App\Models\AdminAction;
use App\Models\User;
use Livewire\Livewire;

it('renders the activities list page for an admin', function (): void {
    actingAsFilamentAdmin();
    $activities = Activity::factory()->count(3)->create(['status' => ActivityStatusEnum::PENDING->value]);

    Livewire::test(ListActivities::class)
        ->assertOk()
        ->assertCanSeeTableRecords($activities);
});

it('approves a pending activity and logs the admin action', function (): void {
    actingAsFilamentAdmin();
    $activity = Activity::factory()->create(['status' => ActivityStatusEnum::PENDING->value]);

    Livewire::test(ListActivities::class)
        ->callTableAction('approve', $activity);

    $activity->refresh();

    expect($activity->status->value)->toBe(ActivityStatusEnum::APPROVED->value)
        ->and($activity->is_active)->toBeTrue()
        ->and($activity->reviewed_at)->not->toBeNull();

    expect(AdminAction::where('action', 'approve_activity')->exists())->toBeTrue();
});

it('rejects an activity with a reason and logs the admin action', function (): void {
    actingAsFilamentAdmin();
    $activity = Activity::factory()->create(['status' => ActivityStatusEnum::PENDING->value]);

    Livewire::test(ListActivities::class)
        ->callTableAction('reject', $activity, data: ['reason' => 'Dossier incomplet']);

    $activity->refresh();

    expect($activity->status->value)->toBe(ActivityStatusEnum::REJECTED->value)
        ->and($activity->is_active)->toBeFalse()
        ->and($activity->rejection_reason)->toBe('Dossier incomplet');

    expect(AdminAction::where('action', 'reject_activity')->exists())->toBeTrue();
});

it('unlinks google from an activity', function (): void {
    actingAsFilamentAdmin();
    $activity = Activity::factory()->create([
        'status' => ActivityStatusEnum::APPROVED->value,
        'google_place_id' => 'ChIJ_test',
        'google_rating' => 4.5,
    ]);

    Livewire::test(ListActivities::class)
        ->callTableAction('unlinkGoogle', $activity);

    $activity->refresh();

    expect($activity->google_place_id)->toBeNull();
});

it('is not accessible to a non-admin user', function (): void {
    $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);
    $user->assignRole('user');
    $this->actingAs($user, 'web');

    expect(ActivityResource::canViewAny())->toBeFalse();
});
