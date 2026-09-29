<?php

declare(strict_types=1);

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\AgendaResource;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ResourceBooking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Option de séjour « Bain » de 45 minutes, qui se place dans l'agenda.
 */
function bath(ActivityUnitType $unitType): Service
{
    $bath = stayOption($unitType, '20.00');
    $bath->forceFill(['requires_scheduling' => true])->save();
    $bath->prices()->update(['duration_minutes' => 45]);

    return $bath;
}

/**
 * Séjour confirmé de la pension, avec un bain à placer.
 */
function stayWithBath(ActivityUnitType $unitType, Service $bath, BookingStatusEnum $status = BookingStatusEnum::CONFIRMED): BookingItem
{
    $booking = Booking::factory()->confirmed()->status($status)->occupying($unitType)->create();

    return $booking->items()->create([
        'service_id' => $bath->id,
        'status' => BookingItemStatusEnum::TO_SCHEDULE,
        'quantity' => 1,
        'unit_price' => '20.00',
        'subtotal' => '20.00',
        'duration_minutes' => 45,
    ]);
}

/**
 * Heure de Paris un jour du séjour (0 : le jour de l'arrivée).
 */
function duringStay(BookingItem $item, int $day, string $time): string
{
    return CarbonImmutable::parse($item->booking->start_date->toDateString().' '.$time, 'Europe/Paris')->addDays($day)->toIso8601String();
}

describe('stay options', function () {
    it('sells an option placed in the agenda as one line per pass', function () {
        $unitType = dogBoarding();
        $client = User::factory()->create();
        $dog = dogOf($client, $unitType);
        $bath = bath($unitType);
        stripeAuthorizes($this->stripe());

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', stayRequest($unitType, [$dog], ['options' => [['service_id' => $bath->id, 'pet_id' => $dog->id, 'quantity' => 2]]]))
            ->assertCreated()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.quantity', 1)
            ->assertJsonPath('items.0.subtotal', '20.00')
            ->assertJsonPath('items.1.status', 'to_schedule')
            ->assertJsonPath('items_amount', '100.00');
    });

    it('places a stay option in the agenda during the stay, then moves it', function () {
        $unitType = dogBoarding();
        $activity = $unitType->activity;
        $lea = AgendaResource::factory()->scheduledIn($activity)->create(['name' => 'Léa']);
        $item = stayWithBath($unitType, bath($unitType));
        $url = "/api/activities/{$activity->id}/bookings/{$item->booking_id}/items/{$item->id}/schedule";

        $this->withHeaders(asUser($activity->organization->owner))
            ->postJson($url, ['resource_id' => $lea->id, 'starts_at' => duringStay($item, 1, '15:00')])
            ->assertOk()
            ->assertJsonPath('items.0.status', 'scheduled')
            ->assertJsonPath('items.0.resource.name', 'Léa')
            ->assertJsonPath('items.0.starts_at', CarbonImmutable::parse(duringStay($item, 1, '15:00'))->utc()->toISOString())
            ->assertJsonPath('items.0.ends_at', CarbonImmutable::parse(duringStay($item, 1, '15:45'))->utc()->toISOString());

        // Le pro choisit l'heure : un bain le soir du départ, après la fermeture, reste possible.
        $this->postJson($url, ['resource_id' => $lea->id, 'starts_at' => duringStay($item, 2, '20:00')])
            ->assertOk();

        expect(ResourceBooking::sole()->starts_at->toISOString())->toBe(CarbonImmutable::parse(duringStay($item, 2, '20:00'))->utc()->toISOString());
    });

    it('refuses a time outside the stay, or a resource already busy', function () {
        $unitType = dogBoarding();
        $activity = $unitType->activity;
        $lea = AgendaResource::factory()->scheduledIn($activity)->create();
        $item = stayWithBath($unitType, bath($unitType));
        ResourceBooking::factory()->for($lea, 'resource')->between(duringStay($item, 1, '15:30'), duringStay($item, 1, '16:30'))->create();
        $url = "/api/activities/{$activity->id}/bookings/{$item->booking_id}/items/{$item->id}/schedule";
        $this->withHeaders(asUser($activity->organization->owner));

        $this->postJson($url, ['resource_id' => $lea->id, 'starts_at' => duringStay($item, -1, '15:00')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);

        $this->postJson($url, ['resource_id' => $lea->id, 'starts_at' => duringStay($item, 1, '15:00')])
            ->assertConflict();

        expect($item->fresh()->status)->toBe(BookingItemStatusEnum::TO_SCHEDULE);
    });

    it('refuses a resource working elsewhere, an option without a duration, or a request not accepted', function () {
        $unitType = dogBoarding();
        $activity = $unitType->activity;
        $lea = AgendaResource::factory()->scheduledIn($activity)->create();
        $elsewhere = AgendaResource::factory()->for($activity->organization)->create();
        $bath = bath($unitType);
        $this->withHeaders(asUser($activity->organization->owner));

        $item = stayWithBath($unitType, $bath);
        $this->postJson("/api/activities/{$activity->id}/bookings/{$item->booking_id}/items/{$item->id}/schedule", ['resource_id' => $elsewhere->id, 'starts_at' => duringStay($item, 1, '15:00')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resource_id' => __('agenda.resource_not_in_activity')]);

        $item->forceFill(['duration_minutes' => null])->save();
        $this->postJson("/api/activities/{$activity->id}/bookings/{$item->booking_id}/items/{$item->id}/schedule", ['resource_id' => $lea->id, 'starts_at' => duringStay($item, 1, '15:00')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item' => __('agenda.item_not_schedulable')]);

        $pending = stayWithBath($unitType, $bath, BookingStatusEnum::PENDING);
        $this->postJson("/api/activities/{$activity->id}/bookings/{$pending->booking_id}/items/{$pending->id}/schedule", ['resource_id' => $lea->id, 'starts_at' => duringStay($pending, 1, '15:00')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('frees the place of a removed option', function () {
        $unitType = dogBoarding();
        $activity = $unitType->activity;
        $lea = AgendaResource::factory()->scheduledIn($activity)->create();
        $item = stayWithBath($unitType, bath($unitType));
        $this->withHeaders(asUser($activity->organization->owner));

        $this->postJson("/api/activities/{$activity->id}/bookings/{$item->booking_id}/items/{$item->id}/schedule", ['resource_id' => $lea->id, 'starts_at' => duringStay($item, 1, '15:00')])
            ->assertOk();

        $this->deleteJson("/api/activities/{$activity->id}/bookings/{$item->booking_id}/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('items.0.status', 'cancelled');

        expect(ResourceBooking::count())->toBe(0);
    });
});

describe('agenda', function () {
    it('shows the organization what keeps its resources busy', function () {
        $salon = appointmentSalon();
        $organization = $salon['activity']->organization;
        $day = now()->addDays(3)->toDateString();
        Booking::factory()->confirmed()->appointment($salon, "{$day}T10:00:00+02:00")->create();
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between("{$day}T14:00:00+02:00", "{$day}T15:00:00+02:00")->create(['note' => 'Dentiste']);
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between(now()->addDays(10)->toIso8601String(), now()->addDays(10)->addHour()->toIso8601String())->create();

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/agenda?from={$day}&to={$day}")
            ->assertOk()
            ->assertJsonPath('timezone', 'Europe/Paris')
            ->assertJsonPath('resources.0.name', 'Léa')
            ->assertJsonCount(2, 'entries')
            ->assertJsonPath('entries.0.kind', 'booking')
            ->assertJsonPath('entries.0.booking.service.name', 'Toilettage')
            ->assertJsonPath('entries.1.kind', 'absence')
            ->assertJsonPath('entries.1.note', 'Dentiste')
            ->assertJsonCount(0, 'to_schedule');
    });

    it('lists the accepted stay options left to place during the period', function () {
        $unitType = dogBoarding();
        $organization = $unitType->activity->organization;
        $option = stayWithBath($unitType, bath($unitType));
        stayWithBath($unitType, bath($unitType), BookingStatusEnum::PENDING);
        $stay = $option->booking->start_date->toDateString();
        $before = $option->booking->start_date->subDays(2)->toDateString();

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/agenda?from={$stay}&to={$stay}")
            ->assertOk()
            ->assertJsonCount(1, 'to_schedule')
            ->assertJsonPath('to_schedule.0.id', $option->id)
            ->assertJsonPath('to_schedule.0.duration_minutes', 45);

        $this->getJson("/api/organizations/{$organization->id}/agenda?from={$before}&to={$before}")
            ->assertOk()
            ->assertJsonCount(0, 'to_schedule');
    });

    it('shows an activity team its own agenda, hiding the bookings of the other activities', function () {
        $salon = appointmentSalon();
        $organization = $salon['activity']->organization;
        $day = now()->addDays(3)->toDateString();
        $other = Activity::factory()->for($organization)->create();
        $salon['resource']->schedules()->create(['organization_id' => $organization->id, 'activity_id' => $other->id, 'weekday' => 1, 'start_time' => '08:00', 'end_time' => '09:00']);
        Booking::factory()->confirmed()->appointment($salon, "{$day}T10:00:00+02:00")->create();
        Booking::factory()->confirmed()->appointment([...$salon, 'activity' => $other], "{$day}T12:00:00+02:00")->create();
        $employee = memberOf($organization, OrganizationRoleEnum::EMPLOYEE, $salon['activity']->id);

        $this->withHeaders(asUser($employee))
            ->getJson("/api/organizations/{$organization->id}/agenda?from={$day}&to={$day}")
            ->assertForbidden();

        $this->getJson("/api/organizations/{$organization->id}/agenda?from={$day}&to={$day}&activity_id={$salon['activity']->id}")
            ->assertOk()
            ->assertJsonCount(2, 'entries')
            ->assertJsonPath('entries.0.booking.activity_id', $salon['activity']->id)
            ->assertJsonPath('entries.1.kind', 'booking')
            ->assertJsonPath('entries.1.booking', null);

        $this->getJson("/api/organizations/{$organization->id}/agenda?from={$day}&to={$day}&activity_id={$other->id}")
            ->assertForbidden();
    });

    it('refuses a period too long', function () {
        $salon = appointmentSalon();
        $organization = $salon['activity']->organization;

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/agenda?from=2026-10-01&to=2026-12-01")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    });
});
