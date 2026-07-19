<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\UserStatusEnum;
use App\Filament\Pages\BookingsDashboard;
use App\Filament\Pages\CommunityDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\FinanceDashboard;
use App\Filament\Pages\ProspectionDashboard;
use App\Filament\Pages\SearchesDashboard;
use App\Filament\Widgets\FinanceStatsWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function (): void {
    Cache::flush();
    actingAsFilamentAdmin();
});

it('renders each of the 6 dashboard pages', function (string $page): void {
    Livewire::test($page)->assertOk();
})->with([
    Dashboard::class,
    FinanceDashboard::class,
    BookingsDashboard::class,
    CommunityDashboard::class,
    SearchesDashboard::class,
    ProspectionDashboard::class,
]);

it('surfaces real KPI values on the overview and finance widgets', function (): void {
    $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE, 'last_seen_at' => now()]);
    $activity = Activity::factory()->create();
    Booking::factory()->count(3)->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'status' => BookingStatusEnum::COMPLETED->value,
        'payment_status' => PaymentStatusEnum::SUCCEEDED->value,
        'total_price' => 100,
        'service_fee' => 8,
        'platform_fee' => 6,
        'activity_amount' => 86,
    ]);
    Prospect::factory()->count(4)->create();
    Cache::flush();

    Livewire::test(OverviewStatsWidget::class, ['lazy' => false])
        ->assertOk()
        ->assertSee('Volume d\'affaires (GMV)')
        ->assertSee('Revenu Kennelo')
        ->assertSee('Utilisateurs actifs (MAU)');

    Livewire::test(FinanceStatsWidget::class, ['lazy' => false])
        ->assertOk()
        ->assertSee('Panier moyen')
        ->assertSee('Remboursements');
});
