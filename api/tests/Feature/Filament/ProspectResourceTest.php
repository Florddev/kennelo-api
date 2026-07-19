<?php

declare(strict_types=1);

use App\Enums\ProspectStatusEnum;
use App\Enums\UserStatusEnum;
use App\Filament\Pages\ProspectsMap;
use App\Filament\Resources\Prospects\Pages\ListProspects;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Jobs\ImportProspectsFromApifyJob;
use App\Models\Prospect;
use App\Models\ProspectImport;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('renders the prospects list page for an admin', function (): void {
    actingAsFilamentAdmin();
    $prospects = Prospect::factory()->count(3)->create();

    Livewire::test(ListProspects::class)
        ->assertOk()
        ->assertCanSeeTableRecords($prospects);
});

it('updates the status of a prospect', function (): void {
    actingAsFilamentAdmin();
    $prospect = Prospect::factory()->create(['status' => ProspectStatusEnum::NON_CONTACTE->value]);

    Livewire::test(ListProspects::class)
        ->callTableAction('updateStatus', $prospect, data: ['status' => ProspectStatusEnum::CONTACTE->value]);

    expect($prospect->refresh()->status->value)->toBe(ProspectStatusEnum::CONTACTE->value);
});

it('reconciles a prospect', function (): void {
    actingAsFilamentAdmin();
    $prospect = Prospect::factory()->create();

    Livewire::test(ListProspects::class)
        ->callTableAction('reconcile', $prospect);

    expect($prospect->refresh()->reconciled_at)->not->toBeNull();
});

it('queues an apify import from the header action', function (): void {
    Queue::fake();
    actingAsFilamentAdmin();

    Livewire::test(ListProspects::class)
        ->callAction('import', data: [
            'location' => 'Lyon, France',
            'max_results' => 5,
        ]);

    Queue::assertPushed(ImportProspectsFromApifyJob::class);
    expect(ProspectImport::query()->where('location', 'Lyon, France')->exists())->toBeTrue();
});

it('renders the prospects map page for an admin', function (): void {
    actingAsFilamentAdmin();
    Prospect::factory()->count(2)->create();

    Livewire::test(ProspectsMap::class)->assertOk();
});

it('is not accessible to a non-admin user', function (): void {
    $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);
    $user->assignRole('user');
    $this->actingAs($user, 'web');

    expect(ProspectResource::canViewAny())->toBeFalse()
        ->and(ProspectsMap::canAccess())->toBeFalse();
});
