<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AdminAction;
use App\Models\User;
use Livewire\Livewire;

it('renders the users list page for an admin', function (): void {
    actingAsFilamentAdmin();
    $users = User::factory()->count(3)->create(['status' => UserStatusEnum::ACTIVE]);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});

it('shows inactive and banned users in the table', function (): void {
    actingAsFilamentAdmin();
    $inactive = User::factory()->create(['status' => UserStatusEnum::INACTIVE]);
    $banned = User::factory()->create(['status' => UserStatusEnum::BANNED]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$inactive, $banned]);
});

it('bans a user through the ban action and logs the admin action', function (): void {
    actingAsFilamentAdmin();
    $target = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);

    Livewire::test(ListUsers::class)
        ->callTableAction('ban', $target, data: [
            'reason' => 'Comportement abusif',
            'banned_until' => null,
        ]);

    $target->refresh();

    expect($target->status)->toBe(UserStatusEnum::BANNED)
        ->and($target->ban_reason)->toBe('Comportement abusif');

    expect(AdminAction::where('action', 'ban')->where('target_user_id', $target->id)->exists())->toBeTrue();
});

it('unbans a banned user', function (): void {
    actingAsFilamentAdmin();
    $target = User::factory()->create([
        'status' => UserStatusEnum::BANNED,
        'ban_reason' => 'test',
        'banned_at' => now(),
    ]);

    Livewire::test(ListUsers::class)
        ->callTableAction('unban', $target);

    $target->refresh();

    expect($target->status)->toBe(UserStatusEnum::ACTIVE)
        ->and($target->ban_reason)->toBeNull();
});

it('cannot ban another admin', function (): void {
    actingAsFilamentAdmin();
    $otherAdmin = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);
    $otherAdmin->assignRole('admin');

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('ban', $otherAdmin);
});

it('cannot ban itself', function (): void {
    $admin = actingAsFilamentAdmin();

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('ban', $admin);
});

it('filters users by the inactive status', function (): void {
    actingAsFilamentAdmin();
    $inactive = User::factory()->create(['status' => UserStatusEnum::INACTIVE]);
    $active = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);

    Livewire::test(ListUsers::class)
        ->filterTable('status', (string) UserStatusEnum::INACTIVE->value)
        ->assertCanSeeTableRecords([$inactive])
        ->assertCanNotSeeTableRecords([$active]);
});
