<?php

declare(strict_types=1);

use App\Enums\ProspectStatusEnum;
use App\Models\Activity;
use App\Models\Address;
use App\Models\Prospect;
use App\Services\Admin\Activity\ActivityAdminService;
use App\Services\Admin\Prospect\ProspectService;

it('marks the prospect as inscrit when reconcile finds a match', function (): void {
    $activity = Activity::factory()->create(['siret' => '12345678900011']);
    $prospect = Prospect::factory()->create(['siret' => '12345678900011']);

    $result = app(ProspectService::class)->reconcile($prospect);

    expect($result->kennelo_activity_id)->toBe($activity->id)
        ->and($result->status)->toBe(ProspectStatusEnum::INSCRIT);
});

it('does not overwrite a manually refused prospect when reconciling', function (): void {
    Activity::factory()->create(['siret' => '22345678900012']);
    $prospect = Prospect::factory()->create([
        'siret' => '22345678900012',
        'status' => ProspectStatusEnum::REFUSE->value,
    ]);

    $result = app(ProspectService::class)->reconcile($prospect);

    expect($result->kennelo_activity_id)->not->toBeNull()
        ->and($result->status)->toBe(ProspectStatusEnum::REFUSE);
});

it('keeps the existing link when re-reconciling finds no match anymore', function (): void {
    $activity = Activity::factory()->create(['siret' => '32345678900013']);
    $prospect = Prospect::factory()->create([
        'siret' => '32345678900013',
        'kennelo_activity_id' => $activity->id,
        'status' => ProspectStatusEnum::INSCRIT->value,
    ]);

    $activity->update(['siret' => '99999999900099', 'name' => 'Renommée']);

    $result = app(ProspectService::class)->reconcile($prospect);

    expect($result->kennelo_activity_id)->toBe($activity->id);
});

it('auto-reconciles a matching prospect by siret when an activity is approved', function (): void {
    $admin = adminUser();
    $activity = Activity::factory()->create(['siret' => '98765432100019']);
    $prospect = Prospect::factory()->create(['siret' => '98765432100019']);

    app(ActivityAdminService::class)->approve($activity, $admin);

    $prospect->refresh();

    expect($prospect->kennelo_activity_id)->toBe($activity->id)
        ->and($prospect->status)->toBe(ProspectStatusEnum::INSCRIT)
        ->and($prospect->reconciled_at)->not->toBeNull();
});

it('auto-reconciles a matching prospect by name and city when an activity is approved', function (): void {
    $admin = adminUser();
    $address = Address::factory()->create(['city' => 'Lyon']);
    $activity = Activity::factory()->create([
        'siret' => '11122233300015',
        'name' => 'Pension Wouf',
        'address_id' => $address->id,
    ]);
    $prospect = Prospect::factory()->create([
        'siret' => null,
        'name' => 'Pension Wouf',
        'city' => 'Lyon',
    ]);

    app(ActivityAdminService::class)->approve($activity, $admin);

    expect($prospect->refresh()->status)->toBe(ProspectStatusEnum::INSCRIT);
});

it('does not auto-link a homonym prospect carrying a different siret', function (): void {
    $admin = adminUser();
    $address = Address::factory()->create(['city' => 'Lyon']);
    $activity = Activity::factory()->create([
        'siret' => '44455566600017',
        'name' => 'Pension Wouf',
        'address_id' => $address->id,
    ]);
    $homonym = Prospect::factory()->create([
        'siret' => '77788899900018',
        'name' => 'Pension Wouf',
        'city' => 'Lyon',
        'status' => ProspectStatusEnum::REFUSE->value,
    ]);

    app(ActivityAdminService::class)->approve($activity, $admin);

    $homonym->refresh();

    expect($homonym->kennelo_activity_id)->toBeNull()
        ->and($homonym->status)->toBe(ProspectStatusEnum::REFUSE);
});

it('does not dispatch any automatic company lookup when approving an activity without siret', function (): void {
    $admin = adminUser();
    $activity = Activity::factory()->create(['siret' => null]);

    $approved = app(ActivityAdminService::class)->approve($activity, $admin);

    expect($approved->siret)->toBeNull();
});
