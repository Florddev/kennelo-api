<?php

declare(strict_types=1);

use App\Models\ActivityUnitType;
use App\Models\Address;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserAddress;

function travellingBoarding(): ActivityUnitType
{
    $unitType = dogBoarding();
    $unitType->activity->forceFill(['serves_at_pro' => true, 'serves_at_client' => true, 'service_radius_km' => 30])->save();
    $unitType->activity->address->update(['latitude' => 45.7640, 'longitude' => 4.8357]);
    $unitType->activity->travelFeeTiers()->createMany([
        ['up_to_km' => 15, 'fee' => '12.00'],
        ['up_to_km' => 5, 'fee' => '5.00'],
    ]);

    return $unitType;
}

function homeOf(User $client, float $latitude, float $longitude): UserAddress
{
    return UserAddress::factory()->for($client)->for(Address::factory()->state(['latitude' => $latitude, 'longitude' => $longitude]))->create();
}

describe('management', function () {
    it('lets the team replace the travel fees, shown to clients in increasing distance', function () {
        $unitType = travellingBoarding();
        $activity = $unitType->activity;

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/travel-fees", ['tiers' => [
                ['up_to_km' => 20, 'fee' => '15'],
                ['up_to_km' => 10, 'fee' => '0'],
            ]])
            ->assertOk()
            ->assertExactJson([
                ['up_to_km' => 10, 'fee' => '0.00'],
                ['up_to_km' => 20, 'fee' => '15.00'],
            ]);

        $this->getJson("/api/activities/{$activity->id}/travel-fees")
            ->assertOk()
            ->assertJsonCount(2);

        $this->getJson("/api/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('travel_fees.1.fee', '15.00');
    });

    it('refuses two tiers for the same distance or beyond the maximum radius', function () {
        $activity = travellingBoarding()->activity;

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/travel-fees", ['tiers' => [
                ['up_to_km' => 10, 'fee' => '5'],
                ['up_to_km' => 10, 'fee' => '8'],
                ['up_to_km' => (int) config('activities.max_radius_km') + 1, 'fee' => '8'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiers.0.up_to_km', 'tiers.1.up_to_km', 'tiers.2.up_to_km']);
    });

    it('keeps the travel fees to the team', function () {
        $activity = travellingBoarding()->activity;

        $this->withHeaders(asUser(User::factory()->create()))
            ->putJson("/api/activities/{$activity->id}/travel-fees", ['tiers' => []])
            ->assertNotFound();

        expect($activity->travelFeeTiers()->count())->toBe(2);
    });
});

describe('quote', function () {
    it('adds the fee of the first tier covering the distance, before the Kennelo fee', function () {
        $unitType = travellingBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['location' => 'at_client', 'address_id' => homeOf($client, 45.7719, 4.8902)->id]))
            ->assertOk()
            ->assertJsonPath('travel_fee', '5.00')
            ->assertJsonPath('items_amount', '65.00')
            ->assertJsonPath('service_fee', '5.20')
            ->assertJsonPath('total_price', '70.20');

        $this->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['location' => 'at_client', 'address_id' => homeOf($client, 45.8540, 4.8357)->id]))
            ->assertOk()
            ->assertJsonPath('travel_fee', '12.00');
    });

    it('applies the last tier between it and the radius of the activity', function () {
        $unitType = travellingBoarding();
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [dogOf($client, $unitType)], ['location' => 'at_client', 'address_id' => homeOf($client, 45.9440, 4.8357)->id]))
            ->assertOk()
            ->assertJsonPath('travel_fee', '12.00');
    });

    it('charges no travel at the place of the professional, nor without tiers', function () {
        $unitType = travellingBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['location' => 'at_pro']))
            ->assertOk()
            ->assertJsonPath('travel_fee', '0.00');

        $unitType->activity->travelFeeTiers()->delete();

        $this->postJson('/api/bookings/quote', stayRequest($unitType, [$dog], ['location' => 'at_client', 'address_id' => homeOf($client, 45.7719, 4.8902)->id]))
            ->assertOk()
            ->assertJsonPath('travel_fee', '0.00');
    });

    it('freezes the travel fee on the booking', function () {
        $unitType = travellingBoarding();
        $client = User::factory()->create();
        stripeAuthorizes($this->stripe());

        $id = $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [dogOf($client, $unitType)], ['location' => 'at_client', 'address_id' => homeOf($client, 45.7719, 4.8902)->id]))
            ->assertCreated()
            ->assertJsonPath('travel_fee', '5.00')
            ->json('id');

        $unitType->activity->travelFeeTiers()->update(['fee' => '9.00']);

        expect(Booking::findOrFail($id)->travel_fee)->toBe('5.00');
    });
});
