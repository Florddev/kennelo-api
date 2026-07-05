<?php

declare(strict_types=1);

use App\Enums\ActivityStatusEnum;
use App\Enums\AdminActionTypeEnum;
use App\Models\Activity;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('admin can list activities', function () {
    $admin = adminUser();
    Activity::factory()->count(3)->create();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/activities')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'status', 'is_professional']], 'meta']);
});

it('admin can filter activities by status', function () {
    $admin = adminUser();
    Activity::factory()->create(['status' => ActivityStatusEnum::PENDING->value]);
    Activity::factory()->create(['status' => ActivityStatusEnum::APPROVED->value]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/activities?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending');
});

it('admin can approve an activity', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create([
        'status' => ActivityStatusEnum::PENDING->value,
        'is_active' => false,
    ]);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.is_active', true);

    expect($activity->fresh()->status)->toBe(ActivityStatusEnum::APPROVED);
    expect(AdminAction::where('action', AdminActionTypeEnum::APPROVE_ACTIVITY->value)->exists())->toBeTrue();
});

it('admin can reject an activity with a reason', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create(['status' => ActivityStatusEnum::PENDING->value]);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/reject", ['reason' => 'SIRET invalide'])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.rejection_reason', 'SIRET invalide');

    expect($activity->fresh()->is_active)->toBeFalse();
});

it('reject requires a reason', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/reject", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

it('admin can verify a company against the gov api', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create(['siret' => '12345678900011']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [[
                'siren' => '123456789',
                'nom_complet' => 'PENSION TEST',
                'activite_principale' => '96.09Z',
                'etat_administratif' => 'A',
                'siege' => ['siret' => '12345678900011'],
            ]],
        ]),
    ]);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/verify-company")
        ->assertOk()
        ->assertJsonPath('data.ape_code', '96.09Z')
        ->assertJsonPath('data.siren', '123456789');

    expect($activity->fresh()->company_verified_at)->not->toBeNull();
});

it('admin can update an activity', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/activities/{$activity->id}", ['name' => 'Nouveau nom'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nouveau nom');
});

it('forbids non-admin from moderating activities', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/activities')
        ->assertForbidden();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/activities/{$activity->id}/approve")
        ->assertForbidden();
});
