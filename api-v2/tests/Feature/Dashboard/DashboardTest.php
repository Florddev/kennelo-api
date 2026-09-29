<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10 10:00', 'Europe/Paris'));
});

it('sums up the day, the requests, the occupancy, the rating and the revenue of an activity', function () {
    $unitType = dogBoarding(units: 2);
    $activity = $unitType->activity;
    $arriving = Booking::factory()->confirmed()->occupying($unitType)->between('2026-03-10', '2026-03-12')->create();
    $arriving->pets()->attach(Pet::factory()->create()->id);
    Booking::factory()->confirmed()->status(BookingStatusEnum::IN_PROGRESS)->occupying($unitType)->between('2026-03-08', '2026-03-10')->create(['activity_amount' => '40.00']);
    Booking::factory()->occupying($unitType)->between('2026-03-20', '2026-03-22')->create();
    Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->occupying($unitType)->between('2026-02-01', '2026-02-03')->create(['activity_amount' => '100.00']);
    Review::factory()->for(Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->occupying($unitType)->between('2025-12-01', '2025-12-03'))->rating('4.0')->published()->create();

    $response = $this->withHeaders(asUser($activity->organization->owner))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk()
        ->assertJsonPath('date', '2026-03-10')
        ->assertJsonPath('today', ['arrivals' => 1, 'departures' => 1, 'appointments' => 0, 'pets_in_care' => 1])
        ->assertJsonPath('pending_requests', 1)
        ->assertJsonPath('upcoming.*.id', [$arriving->id])
        ->assertJsonPath('occupancy.0.capacity', 2)
        // La nuit du 10 au 11 : seule la réservation qui arrive l'occupe ; l'autre part le matin.
        ->assertJsonPath('occupancy.0.occupied', 1)
        ->assertJsonPath('rating.average', 4)
        ->assertJsonPath('revenue.current_month', '95.20')
        ->assertJsonPath('revenue.previous_month', '100.00');

    expect($response->json('revenue.series'))->toHaveCount(6)
        ->and($response->json('revenue.series.5'))->toBe(['month' => '2026-03', 'amount' => '95.20']);

    // Sans finance.view, pas de chiffre d'affaires.
    $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::EMPLOYEE, $activity->id)))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk()
        ->assertJsonPath('revenue', null);
});

it('keeps the dashboards to the team', function () {
    $activity = dogBoarding()->activity;
    $organization = $activity->organization;

    $this->withHeaders(asUser($organization->owner))->getJson("/api/organizations/{$organization->id}/dashboard")->assertOk()->assertJsonPath('pending_requests', 0);
    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::EMPLOYEE, $activity->id)))->getJson("/api/organizations/{$organization->id}/dashboard")->assertForbidden();
    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))->getJson("/api/activities/{$activity->id}/dashboard")->assertForbidden();
    $this->withHeaders(asUser(User::factory()->create()))->getJson("/api/activities/{$activity->id}/dashboard")->assertNotFound();
});
