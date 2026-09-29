<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(now('Europe/Paris')->setTime(10, 0));
});

/**
 * Réservation de $pet dans l'activité, du $from au $to (décalages en jours depuis aujourd'hui).
 */
function careOf(Activity $activity, Pet $pet, int $from, int $to, BookingStatusEnum $status = BookingStatusEnum::CONFIRMED): Booking
{
    $booking = Booking::factory()->confirmed()->status($status)
        ->between(today()->addDays($from)->toDateString(), today()->addDays($to)->toDateString())
        ->create(['activity_id' => $activity->id, 'user_id' => $pet->user_id]);
    $booking->pets()->attach($pet->id);

    return $booking;
}

it('lists the pets in care today, with their owner', function () {
    $activity = Activity::factory()->bookable()->create();
    $owner = User::factory()->create(['phone' => '+33612345678']);
    $rex = Pet::factory()->for($owner)->create(['name' => 'Rex', 'microchip_number' => '250269812345678', 'has_microchip' => true]);
    $stay = careOf($activity, $rex, -1, 1, BookingStatusEnum::IN_PROGRESS);
    careOf($activity, Pet::factory()->create(), 2, 4);
    careOf($activity, Pet::factory()->create(), -5, -2, BookingStatusEnum::COMPLETED);
    careOf($activity, Pet::factory()->create(), 0, 2, BookingStatusEnum::PENDING);
    careOf(Activity::factory()->bookable()->create(), Pet::factory()->create(), -1, 1);

    $this->withHeaders(asUser($activity->organization->owner))
        ->getJson("/api/organizations/{$activity->organization_id}/in-care-pets")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Rex')
        ->assertJsonPath('0.microchip_number', '250269812345678')
        ->assertJsonPath('0.owner.phone', '+33612345678')
        ->assertJsonPath('0.booking.id', $stay->id)
        ->assertJsonPath('0.booking.activity.name', $activity->name);
});

it('finds a scanned pet only among the pets in care', function () {
    $activity = Activity::factory()->bookable()->create();
    $inCare = Pet::factory()->create(['microchip_number' => '250269800000001', 'has_microchip' => true]);
    $gone = Pet::factory()->create(['microchip_number' => '250269800000002', 'has_microchip' => true]);
    careOf($activity, $inCare, 0, 2);
    careOf($activity, $gone, -6, -3, BookingStatusEnum::COMPLETED);
    $this->withHeaders(asUser($activity->organization->owner));
    $url = "/api/organizations/{$activity->organization_id}/in-care-pets?microchip=";

    $this->getJson($url.'250269800000001')->assertJsonPath('*.id', [$inCare->id]);
    $this->getJson($url.'250269800000002')->assertJsonCount(0);
    $this->getJson($url.'999')->assertJsonCount(0);
});

it('shows each member the pets of the activities where they see the bookings', function () {
    $kennel = Activity::factory()->bookable()->create();
    $cattery = Activity::factory()->bookable()->create(['organization_id' => $kennel->organization_id]);
    $dog = Pet::factory()->create();
    careOf($kennel, $dog, 0, 1);
    careOf($cattery, Pet::factory()->create(), 0, 1);
    $organization = $kennel->organization;

    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::EMPLOYEE, $kennel->id)))
        ->getJson("/api/organizations/{$organization->id}/in-care-pets")
        ->assertOk()
        ->assertJsonPath('*.id', [$dog->id]);
    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
        ->getJson("/api/organizations/{$organization->id}/in-care-pets")
        ->assertForbidden();
    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson("/api/organizations/{$organization->id}/in-care-pets")
        ->assertNotFound();
});
