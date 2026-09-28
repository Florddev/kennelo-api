<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatusEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Profession;
use App\Models\ResourceBooking;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00', 'Europe/Paris'));
});

/**
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function availableIds(array $query): array
{
    return test()->getJson('/api/explore/search?'.http_build_query($query))->assertOk()->json('data.*.id');
}

function catType(): AnimalType
{
    return AnimalType::query()->where('code', 'cat')->first() ?? AnimalType::factory()->cat()->create();
}

function unitFor(Activity $activity, AnimalType $species, int $quantity = 1): ActivityUnitType
{
    return ActivityUnitType::factory()->for($activity)->forSpecies($species)->priced('20.00')->create(['quantity' => $quantity]);
}

function stayOver(ActivityUnitType $unitType, string $start, string $end): Booking
{
    return Booking::factory()->confirmed()->occupying($unitType)->create(['start_date' => $start, 'end_date' => $end]);
}

describe('stays', function () {
    it('keeps a boarding with room for the animals every night', function () {
        $boarding = dogBoarding(units: 2);
        stayOver($boarding, '2026-10-15', '2026-10-17');
        $dates = ['start_date' => '2026-10-15', 'end_date' => '2026-10-17'];

        expect(availableIds([...$dates, 'animals' => ['dog' => 1]]))->toBe([$boarding->activity_id])
            ->and(availableIds([...$dates, 'animals' => ['dog' => 2]]))->toBe([]);

        stayOver($boarding, '2026-10-16', '2026-10-17');

        expect(availableIds([...$dates, 'animals' => ['dog' => 1]]))->toBe([])
            ->and(availableIds(['start_date' => '2026-10-17', 'end_date' => '2026-10-19', 'animals' => ['dog' => 2]]))->toBe([$boarding->activity_id]);
    });

    it('counts the departure day as free, and one night when only the arrival is given', function () {
        $boarding = dogBoarding(units: 1);
        stayOver($boarding, '2026-10-15', '2026-10-17');

        expect(availableIds(['start_date' => '2026-10-17', 'end_date' => '2026-10-18']))->toBe([$boarding->activity_id])
            ->and(availableIds(['start_date' => '2026-10-16']))->toBe([])
            ->and(availableIds(['start_date' => '2026-10-17']))->toBe([$boarding->activity_id]);
    });

    it('leaves out a boarding closed one of the nights or below its minimum stay', function () {
        $closed = dogBoarding();
        $closed->activity->availabilities()->create(['date' => '2026-10-16', 'status' => AvailabilityStatusEnum::CLOSED]);
        $longStays = dogBoarding();
        $longStays->activity->periodSettings()->update(['min_stay' => 3]);

        expect(availableIds(['start_date' => '2026-10-15', 'end_date' => '2026-10-17']))->toBe([])
            ->and(availableIds(['start_date' => '2026-10-20', 'end_date' => '2026-10-23']))->toEqualCanonicalizing([$closed->activity_id, $longStays->activity_id]);
    });

    it('places each species in the units that accept it', function () {
        $dog = dogBoarding(units: 1)->animalTypes->firstOrFail();
        $cat = catType();
        $both = Activity::factory()->bookable()->for(Profession::factory()->stay()->forSpecies($dog, $cat))->forSpecies($dog, $cat)->create();
        unitFor($both, $dog);
        unitFor($both, $cat);
        $dates = ['start_date' => '2026-10-15', 'end_date' => '2026-10-17'];

        expect(availableIds([...$dates, 'animals' => ['dog' => 1, 'cat' => 1]]))->toBe([$both->id])
            ->and(availableIds([...$dates, 'animals' => ['cat' => 2]]))->toBe([])
            ->and(availableIds([...$dates, 'animals' => ['dog' => 2]]))->toBe([]);
    });

    it('filters on the species welcomed without dates', function () {
        $boarding = dogBoarding();
        $cattery = Activity::factory()->bookable()->for(Profession::factory()->stay()->forSpecies(catType()))->forSpecies(catType())->create();

        expect(availableIds(['animals' => ['cat' => 3]]))->toBe([$cattery->id])
            ->and(availableIds(['animals' => ['dog' => 1]]))->toBe([$boarding->activity_id]);
    });
});

describe('appointments', function () {
    it('keeps a salon with a free slot over the dates', function () {
        $salon = appointmentSalon();
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between('2026-10-06T09:00:00+02:00', '2026-10-06T18:00:00+02:00')->create();

        expect(availableIds(['start_date' => '2026-10-06']))->toBe([])
            ->and(availableIds(['start_date' => '2026-10-06', 'end_date' => '2026-10-07']))->toBe([$salon['activity']->id]);
    });

    it('needs a slot long enough for every animal', function () {
        $salon = appointmentSalon(duration: 60);
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between('2026-10-06T09:00:00+02:00', '2026-10-06T13:00:00+02:00')->create();
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between('2026-10-06T14:00:00+02:00', '2026-10-06T18:00:00+02:00')->create();

        expect(availableIds(['start_date' => '2026-10-06', 'animals' => ['dog' => 1]]))->toBe([$salon['activity']->id])
            ->and(availableIds(['start_date' => '2026-10-06', 'animals' => ['dog' => 2]]))->toBe([]);
    });
});

describe('validation', function () {
    it('refuses dates in the past, reversed or too far apart, and unknown species', function () {
        AnimalType::factory()->dog()->create();

        $this->getJson('/api/explore/search?'.http_build_query([
            'start_date' => '2026-10-01',
            'animals' => ['unicorn' => 1],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['start_date']);

        $this->getJson('/api/explore/search?'.http_build_query(['start_date' => '2026-10-10', 'end_date' => '2026-10-09']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->getJson('/api/explore/search?'.http_build_query(['start_date' => '2026-10-10', 'end_date' => '2027-10-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->getJson('/api/explore/search?'.http_build_query(['end_date' => '2026-10-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);

        $this->getJson('/api/explore/search?'.http_build_query(['animals' => ['unicorn' => 1, 'dog' => 1]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['animals']);
    });
});

describe('available this weekend', function () {
    it('lists the nearby activities free on the coming weekend', function () {
        $free = dogBoarding(units: 1);
        $free->activity->address->update(['latitude' => 45.7719, 'longitude' => 4.8902]);
        $full = dogBoarding(units: 1);
        $full->activity->address->update(['latitude' => 45.7719, 'longitude' => 4.8902]);
        stayOver($full, '2026-10-10', '2026-10-11');

        $this->getJson('/api/explore/activities/sections/available_this_weekend?lat=45.7640&lng=4.8357')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$free->activity_id]);

        $this->getJson('/api/explore/activities/sections/available_this_weekend')->assertNotFound();
    });
});
