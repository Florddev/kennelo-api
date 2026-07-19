<?php

declare(strict_types=1);

use App\Filament\Pages\Settings;
use App\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Models\SubscriptionPlan;
use App\Services\Setting\SettingService;
use Livewire\Livewire;

it('renders the settings page and pre-fills rates as percentages', function (): void {
    actingAsFilamentAdmin();

    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'user_service_fee_rate' => 8.0,
            'host_commission_rate' => 6.0,
        ]);
});

it('saves settings converting percentages back to decimals', function (): void {
    actingAsFilamentAdmin();

    Livewire::test(Settings::class)
        ->fillForm([
            'user_service_fee_rate' => 10,
            'host_commission_rate' => 7,
            'acceptance_window_hours' => 48,
            'payout_delay_hours' => 24,
            'reminder_after_hours' => 36,
            'currency' => 'eur',
            'tier3_enabled' => false,
            'soft_disable_activities' => false,
            'soft_disable_cycles' => false,
            'soft_disable_photos' => false,
        ])
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(SettingService::class)->all();

    expect((float) $settings['user_service_fee_rate'])->toBe(0.1)
        ->and((float) $settings['host_commission_rate'])->toBe(0.07)
        ->and((int) $settings['acceptance_window_hours'])->toBe(48);
});

it('renders the subscription plans list', function (): void {
    actingAsFilamentAdmin();
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 29.99,
        'currency' => 'eur',
        'commission_rate' => '0.0600',
        'limits' => ['max_activities' => 5],
        'is_active' => true,
    ]);

    Livewire::test(ListSubscriptionPlans::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$plan]);
});

it('edits a plan converting commission between percent and decimal', function (): void {
    actingAsFilamentAdmin();
    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price_monthly' => 29.99,
        'currency' => 'eur',
        'commission_rate' => '0.0600',
        'limits' => ['max_activities' => 5],
        'is_active' => true,
    ]);

    Livewire::test(EditSubscriptionPlan::class, ['record' => $plan->id])
        ->assertOk()
        ->assertSchemaStateSet(['commission_rate' => 6.0])
        ->fillForm(['commission_rate' => 8])
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $plan->refresh()->commission_rate)->toBe(0.08);
});
