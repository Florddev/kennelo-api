<?php

declare(strict_types=1);

use App\Enums\AdminActionTypeEnum;
use App\Enums\OrganizationStatusEnum;
use App\Models\AdminAction;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

it('lists companies filtered by status', function () {
    Organization::factory()->create();
    $verified = Organization::factory()->verified()->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/organizations?status=verified')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $verified->id)
        ->assertJsonPath('data.0.owner.id', $verified->owner_id);
});

it('approves a company, notifies its owner and logs the action', function () {
    Notification::fake();
    $admin = adminUser();
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/organizations/{$organization->id}/approve")
        ->assertOk()
        ->assertJsonPath('status', 'verified');

    $organization->refresh();

    expect($organization->status)->toBe(OrganizationStatusEnum::VERIFIED)
        ->and($organization->verified_at)->not->toBeNull()
        ->and($organization->reviewed_by)->toBe($admin->id);
    expect(AdminAction::where('action', AdminActionTypeEnum::APPROVE_ORGANIZATION)->sole()->metadata)
        ->toBe(['organization_id' => $organization->id]);
    Notification::assertSentTo($organization->owner, AppNotification::class);
});

it('rejects a company with a reason', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/organizations/{$organization->id}/reject", ['reason' => 'SIREN radié'])
        ->assertOk()
        ->assertJsonPath('status', 'rejected')
        ->assertJsonPath('rejection_reason', 'SIREN radié');
});

it('requires a reason to reject or suspend a company', function (string $action) {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/organizations/{$organization->id}/{$action}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
})->with(['reject', 'suspend']);

it('suspends a verified company', function () {
    $organization = Organization::factory()->verified()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/organizations/{$organization->id}/suspend", ['reason' => 'Fraude signalée'])
        ->assertOk()
        ->assertJsonPath('status', 'suspended');
});

it('stores the register data when checking the SIREN', function () {
    Http::preventStrayRequests();
    Http::fake(['recherche-entreprises.api.gouv.fr/search*' => Http::response(['results' => [[
        'siren' => '123456789',
        'nom_complet' => 'PENSION DES LILAS',
        'etat_administratif' => 'A',
        'siege' => ['code_postal' => '69003', 'libelle_commune' => 'LYON'],
    ]]])]);
    $organization = Organization::factory()->create(['siren' => '123456789']);

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/organizations/{$organization->id}/verify-company")
        ->assertOk()
        ->assertJsonPath('verification_data.legal_name', 'PENSION DES LILAS')
        ->assertJsonPath('status', 'pending');
});

it('refuses to check an individual without SIREN', function () {
    Http::preventStrayRequests();
    $organization = Organization::factory()->individual()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/organizations/{$organization->id}/verify-company")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['siren' => __('organization.no_siren')]);
});

it('forbids the admin routes to a company owner', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/admin/organizations/{$organization->id}/approve")
        ->assertForbidden();

    expect($organization->fresh()->status)->toBe(OrganizationStatusEnum::PENDING);
});

it('grants an admin no permission inside a company', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson("/api/organizations/{$organization->id}")
        ->assertNotFound();
});

it('keeps the account of a company owner', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->deleteJson("/api/admin/users/{$organization->owner_id}")
        ->assertUnprocessable()
        ->assertJsonPath('message', __('account.owns_organization'));

    expect(User::find($organization->owner_id))->not->toBeNull();
});
