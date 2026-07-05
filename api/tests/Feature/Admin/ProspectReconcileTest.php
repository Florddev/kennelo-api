<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('reconciles a prospect to a kennelo activity by siret', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create(['siret' => '12345678900011']);
    $prospect = Prospect::factory()->create(['siret' => '12345678900011']);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/prospects/{$prospect->id}/reconcile")
        ->assertOk()
        ->assertJsonPath('data.kennelo_activity_id', $activity->id)
        ->assertJsonPath('data.is_registered', true);
});

it('enriches a prospect with siret from the gov api when unmatched', function () {
    $admin = adminUser();
    $prospect = Prospect::factory()->create(['siret' => null, 'siren' => null]);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [[
                'siren' => '123456789',
                'nom_complet' => 'PENSION TEST',
                'activite_principale' => '96.09Z',
                'etat_administratif' => 'A',
                'siege' => [
                    'siret' => '12345678900011',
                    'departement' => '69',
                ],
            ]],
        ]),
    ]);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/prospects/{$prospect->id}/reconcile")
        ->assertOk()
        ->assertJsonPath('data.siret', '12345678900011')
        ->assertJsonPath('data.ape_code', '96.09Z');
});

it('forbids non-admin from reconciling', function () {
    $user = User::factory()->create();
    $prospect = Prospect::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/prospects/{$prospect->id}/reconcile")
        ->assertForbidden();
});
