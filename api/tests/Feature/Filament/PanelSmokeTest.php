<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use App\Models\User;

it('renders full panel pages (layout + user menu) for an admin', function (string $path): void {
    actingAsFilamentAdmin();

    $this->followingRedirects()->get($path)->assertOk();
})->with([
    '/admin',
    '/admin/finance',
    '/admin/reservations',
    '/admin/communaute',
    '/admin/recherches-stats',
    '/admin/prospection-stats',
    '/admin/users',
    '/admin/activities',
    '/admin/prospects',
    '/admin/prospects-map',
    '/admin/search-logs',
    '/admin/admin-actions',
    '/admin/review-reports',
    '/admin/subscription-plans',
    '/admin/settings',
]);

it('renders the panel for an admin whose display name would otherwise be empty', function (): void {
    $admin = User::factory()->create([
        'first_name' => '',
        'last_name' => '',
        'status' => UserStatusEnum::ACTIVE,
    ]);
    $admin->assignRole('admin');
    $this->actingAs($admin, 'web');

    expect($admin->getFilamentName())->toBe($admin->email);

    $this->followingRedirects()->get('/admin')->assertOk();
});

it('renders lazy skeleton placeholders before dashboard widgets load', function (): void {
    actingAsFilamentAdmin();

    $this->get('/admin')
        ->assertOk()
        ->assertSee('k-skeleton', escape: false)
        ->assertSee('@keyframes k-pulse', escape: false)
        ->assertSee('lazyIsolated&quot;:false', escape: false);
});
