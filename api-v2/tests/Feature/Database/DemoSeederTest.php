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

    expect(Activity::query()->bookable()->count())->toBe(3);
    $this->getJson('/api/explore/search')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson("/api/activities/{$pension->id}")->assertOk()->assertJsonPath('rating.count', 1);

    $this->withHeaders(asUser($camille))->getJson('/api/bookings')->assertOk()->assertJsonCount(4, 'data');
    $this->getJson('/api/conversations')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/user/invoices')->assertOk()->assertJsonCount(8, 'data');

    $this->withHeaders(asUser($pro))
        ->getJson("/api/organizations/{$organization->id}/in-care-pets")
        ->assertOk()
        ->assertJsonPath('*.name', ['Mina']);
    $this->getJson("/api/organizations/{$organization->id}/dashboard")
        ->assertOk()
        ->assertJsonPath('pending_requests', 1);
});
