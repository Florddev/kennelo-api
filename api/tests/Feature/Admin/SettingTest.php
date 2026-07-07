<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use App\Services\Setting\SettingService;

it('forbids non-admin from reading settings', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/settings')
        ->assertForbidden();
});

it('returns settings grouped by category with defaults', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/settings')
        ->assertOk()
        ->assertJsonPath('data.fees.user_service_fee_rate', '0.08')
        ->assertJsonPath('data.booking.acceptance_window_hours', 72)
        ->assertJsonPath('data.stripe.currency', 'eur');
});

it('updates a setting and persists it', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->putJson('/api/admin/settings', [
            'values' => ['user_service_fee_rate' => '0.12'],
        ])
        ->assertOk()
        ->assertJsonPath('data.fees.user_service_fee_rate', '0.12');

    expect(Setting::where('key', 'user_service_fee_rate')->value('value'))->toBe('0.12');
});

it('invalidates the cache so business code reads the new value', function () {
    $admin = adminUser();

    expect(setting('user_service_fee_rate'))->toBe('0.08');

    $this->withHeaders(asUser($admin))
        ->putJson('/api/admin/settings', [
            'values' => ['user_service_fee_rate' => '0.15'],
        ])
        ->assertOk();

    expect(app(SettingService::class)->get('user_service_fee_rate'))->toBe('0.15');
});

it('rejects values outside the allowed bounds', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->putJson('/api/admin/settings', [
            'values' => ['user_service_fee_rate' => '2'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.user_service_fee_rate']);
});

it('ignores unknown setting keys', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->putJson('/api/admin/settings', [
            'values' => ['user_service_fee_rate' => '0.09', 'unknown_key' => 'x'],
        ])
        ->assertOk();

    expect(Setting::where('key', 'unknown_key')->exists())->toBeFalse();
});
