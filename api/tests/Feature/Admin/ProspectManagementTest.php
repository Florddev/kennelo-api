<?php

declare(strict_types=1);

use App\Enums\ProspectStatusEnum;
use App\Jobs\ImportProspectsFromApifyJob;
use App\Models\Prospect;
use App\Models\ProspectImport;
use App\Models\User;
use App\Services\Admin\Prospect\ProspectService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('admin can list prospects', function () {
    $admin = adminUser();
    Prospect::factory()->count(3)->create();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/prospects')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'status', 'is_registered']], 'meta']);
});

it('admin can filter prospects by status', function () {
    $admin = adminUser();
    Prospect::factory()->create(['status' => ProspectStatusEnum::NON_CONTACTE->value]);
    Prospect::factory()->create(['status' => ProspectStatusEnum::INSCRIT->value]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/prospects?status=inscrit')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'inscrit');
});

it('admin can update a prospect status', function () {
    $admin = adminUser();
    $prospect = Prospect::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/prospects/{$prospect->id}/status", ['status' => 'contacte'])
        ->assertOk()
        ->assertJsonPath('data.status', 'contacte');

    expect($prospect->fresh()->status)->toBe(ProspectStatusEnum::CONTACTE);
});

it('admin can assign a prospect to a user', function () {
    $admin = adminUser();
    $member = User::factory()->create();
    $prospect = Prospect::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/prospects/{$prospect->id}/assign", ['assigned_to' => $member->id])
        ->assertOk()
        ->assertJsonPath('data.assigned_to', $member->id);
});

it('admin can add and list notes on a prospect', function () {
    $admin = adminUser();
    $prospect = Prospect::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/prospects/{$prospect->id}/notes", ['body' => 'Rappeler lundi'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Rappeler lundi');

    $this->withHeaders(asUser($admin))
        ->getJson("/api/admin/prospects/{$prospect->id}/notes")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('admin can log a contact on a prospect', function () {
    $admin = adminUser();
    $prospect = Prospect::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/prospects/{$prospect->id}/contacts", [
            'type' => 'call',
            'outcome' => 'Pas de réponse',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'call');
});

it('admin can import prospects from apify synchronously', function () {
    $admin = adminUser();

    Http::fake([
        'api.apify.com/*' => Http::response([
            [
                'title' => 'Pension Test Lyon',
                'address' => '10 Rue de Test, 69003 Lyon, France',
                'city' => 'Lyon',
                'postalCode' => '69003',
                'countryCode' => 'FR',
                'location' => ['lat' => 45.75, 'lng' => 4.85],
                'phoneUnformatted' => '+33612345678',
                'website' => 'https://pension-test.fr',
                'totalScore' => 4.6,
                'reviewsCount' => 42,
                'placeId' => 'ChIJtest123',
                'categoryName' => 'Pension pour chiens',
                'categories' => ['Pension pour chiens'],
            ],
        ]),
    ]);

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/prospects/import', [
            'location' => 'Lyon, France',
            'max_results' => 5,
            'sync' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.imported', 1);

    expect(Prospect::where('google_place_id', 'ChIJtest123')->exists())->toBeTrue();
});

it('import deduplicates by google place id', function () {
    $admin = adminUser();
    Prospect::factory()->create(['google_place_id' => 'ChIJtest123']);

    Http::fake([
        'api.apify.com/*' => Http::response([
            [
                'title' => 'Pension Test Lyon',
                'placeId' => 'ChIJtest123',
                'location' => ['lat' => 45.75, 'lng' => 4.85],
            ],
        ]),
    ]);

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/prospects/import', [
            'location' => 'Lyon, France',
            'sync' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.imported', 0)
        ->assertJsonPath('data.skipped', 1);
});

it('import is queued and returns a tracking id when not synchronous', function () {
    $admin = adminUser();
    Queue::fake();

    $response = $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/prospects/import', ['location' => 'Lyon, France'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonStructure(['data' => ['id', 'location', 'status']]);

    Queue::assertPushed(ImportProspectsFromApifyJob::class);

    expect(ProspectImport::where('id', $response->json('data.id'))
        ->where('requested_by', $admin->id)
        ->exists())->toBeTrue();
});

it('admin can poll an import status', function () {
    $admin = adminUser();
    $import = ProspectImport::factory()->completed(4, 2)->create();

    $this->withHeaders(asUser($admin))
        ->getJson("/api/admin/prospects/imports/{$import->id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.imported_count', 4)
        ->assertJsonPath('data.skipped_count', 2);
});

it('processing an import runs apify and records the result', function () {
    $import = ProspectImport::factory()->create(['location' => 'Lyon, France']);

    Http::fake([
        'api.apify.com/*' => Http::response([
            [
                'title' => 'Pension Async Lyon',
                'placeId' => 'ChIJasync1',
                'location' => ['lat' => 45.75, 'lng' => 4.85],
            ],
        ]),
    ]);

    app(ProspectService::class)->processImport($import->id);

    expect($import->fresh()->status->value)->toBe('completed');
    expect($import->fresh()->imported_count)->toBe(1);
    expect(Prospect::where('google_place_id', 'ChIJasync1')->exists())->toBeTrue();
});

it('a failing import is marked as failed', function () {
    $import = ProspectImport::factory()->create(['location' => 'Lyon, France']);

    Http::fake(['api.apify.com/*' => Http::response('boom', 500)]);

    app(ProspectService::class)->processImport($import->id);

    expect($import->fresh()->status->value)->toBe('failed');
    expect($import->fresh()->error)->not->toBeNull();
});

it('import requires a location', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/prospects/import', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['location']);
});

it('forbids non-admin from listing prospects', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/prospects')
        ->assertForbidden();
});

it('forbids guests from listing prospects', function () {
    $this->getJson('/api/admin/prospects')
        ->assertUnauthorized();
});
