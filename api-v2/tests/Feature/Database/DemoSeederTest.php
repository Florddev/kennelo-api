<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds a demo where each screen has something to show', function () {
    $this->seed(DatabaseSeeder::class);

    $camille = User::query()->where('email', 'camille@kennelo.test')->sole();
    $pro = User::query()->where('email', 'pro@kennelo.test')->sole();
    $organization = $pro->ownedOrganizations()->sole();
    $pension = Activity::query()->where('name', 'Pension Les Pattes du Lac')->sole();

    // La pension, le salon et la pet-sitter sont réservables, donc trouvés par la recherche.
    expect(Activity::query()->bookable()->count())->toBe(3);
    $this->getJson('/api/explore/search')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson("/api/activities/{$pension->id}")->assertOk()->assertJsonPath('data.rating.count', 1);

    // Côté cliente : quatre réservations, une conversation, deux factures par paiement encaissé.
    $this->withHeaders(asUser($camille))->getJson('/api/bookings')->assertOk()->assertJsonCount(4, 'data');
    $this->getJson('/api/conversations')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/user/invoices')->assertOk()->assertJsonCount(8, 'data');

    // Côté entreprise : Mina est en garde, une demande attend, et le tableau de bord a son chiffre d'affaires.
    $this->withHeaders(asUser($pro))
        ->getJson("/api/organizations/{$organization->id}/in-care-pets")
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Mina']);
    $this->getJson("/api/organizations/{$organization->id}/dashboard")
        ->assertOk()
        ->assertJsonPath('data.pending_requests', 1);
});
