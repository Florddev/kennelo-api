<?php

declare(strict_types=1);

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Models\AgendaResource;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\ResourceBooking;
use App\Models\User;
use Carbon\CarbonImmutable;

// Lundi 5 octobre 2026, 8 h à Paris. Le salon ouvre de 9 h à 18 h ; il faut réserver deux heures à l'avance.
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00', 'Europe/Paris'));
});

describe('slots', function () {
    it('lists the free slots long enough for all the pets of the client', function () {
        $salon = appointmentSalon(duration: 60);
        $client = User::factory()->create();
        $pets = [petFor($client, $salon['activity']), petFor($client, $salon['activity'])];

        $this->withHeaders(asUser($client))
            ->getJson("/api/activities/{$salon['activity']->id}/slots?".http_build_query([
                'service_id' => $salon['service']->id,
                'from' => '2026-10-06',
                'to' => '2026-10-06',
                'pet_ids' => [$pets[0]->id, $pets[1]->id],
            ]))
            ->assertOk()
            ->assertJsonPath('meta.timezone', 'Europe/Paris')
            ->assertJsonPath('meta.duration_minutes', 120)
            ->assertJsonPath('meta.resources.0.name', 'Léa')
            ->assertJsonPath('data.0.starts_at', '2026-10-06T07:00:00.000000Z')
            ->assertJsonPath('data.0.ends_at', '2026-10-06T09:00:00.000000Z')
            ->assertJsonPath('data.0.resource_ids', [$salon['resource']->id])
            // De 9 h à 16 h, tous les quarts d'heure.
            ->assertJsonCount(29, 'data');
    });

    it('shows a visitor the slots of the longest duration of the grid', function () {
        $salon = appointmentSalon(duration: 90);

        $this->getJson("/api/activities/{$salon['activity']->id}/slots?service_id={$salon['service']->id}&from=2026-10-06&to=2026-10-06")
            ->assertOk()
            ->assertJsonPath('meta.duration_minutes', 90)
            ->assertJsonPath('data.0.starts_at', '2026-10-06T07:00:00.000000Z');
    });

    it('hides what keeps the resource busy and what comes before the notice', function () {
        $salon = appointmentSalon();
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between('2026-10-06T10:00:00+02:00', '2026-10-06T12:00:00+02:00')->create();

        $starts = collect($this->getJson("/api/activities/{$salon['activity']->id}/slots?service_id={$salon['service']->id}&from=2026-10-05&to=2026-10-06")
            ->assertOk()
            ->json('data'))->pluck('starts_at');

        expect($starts)->toContain('2026-10-05T08:00:00.000000Z', '2026-10-06T07:00:00.000000Z', '2026-10-06T10:00:00.000000Z')
            ->and($starts)->not->toContain('2026-10-05T07:45:00.000000Z', '2026-10-06T08:00:00.000000Z', '2026-10-06T09:45:00.000000Z');
    });

    it('refuses an activity booked by stays, and a period too long', function () {
        $salon = appointmentSalon();
        $boarding = dogBoarding()->activity;

        $this->getJson("/api/activities/{$boarding->id}/slots?service_id={$salon['service']->id}&from=2026-10-06&to=2026-10-06")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activity_id' => __('agenda.appointment_only')]);

        $this->getJson("/api/activities/{$salon['activity']->id}/slots?service_id={$salon['service']->id}&from=2026-10-06&to=2026-12-06")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    });
});

describe('booking', function () {
    it('quotes a grooming for two dogs, one after the other with the same groomer', function () {
        $salon = appointmentSalon(price: '40.00');
        $client = User::factory()->create();
        $pets = [petFor($client, $salon['activity']), petFor($client, $salon['activity'])];

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', appointmentRequest($salon, $pets, '2026-10-06T10:00:00+02:00'))
            ->assertOk()
            ->assertJsonPath('start_date', '2026-10-06')
            ->assertJsonPath('appointment.resource.name', 'Léa')
            ->assertJsonPath('appointment.starts_at', '2026-10-06T08:00:00.000000Z')
            ->assertJsonPath('appointment.ends_at', '2026-10-06T10:00:00.000000Z')
            ->assertJsonPath('appointment.lines.0.pet_id', $pets[0]->id)
            ->assertJsonPath('appointment.lines.1.starts_at', '2026-10-06T09:00:00.000000Z')
            ->assertJsonPath('items_amount', '80.00')
            ->assertJsonPath('service_fee', '6.40')
            ->assertJsonPath('total_price', '86.40');
    });

    it('books an appointment, which holds the slot of the groomer', function () {
        $salon = appointmentSalon();
        $client = User::factory()->create();
        $dog = petFor($client, $salon['activity']);
        stripeAuthorizes($this->stripe());

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings', appointmentRequest($salon, [$dog], '2026-10-06T10:00:00+02:00'))
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('activity.booking_mode', 'appointment')
            ->assertJsonPath('starts_at', '2026-10-06T08:00:00.000000Z')
            ->assertJsonPath('ends_at', '2026-10-06T09:00:00.000000Z')
            ->assertJsonPath('items.0.status', 'scheduled')
            ->assertJsonPath('items.0.resource.name', 'Léa')
            ->assertJsonPath('pets.0.id', $dog->id);

        $entry = ResourceBooking::sole();

        expect($entry->resource_id)->toBe($salon['resource']->id)
            ->and($entry->starts_at->toISOString())->toBe('2026-10-06T08:00:00.000000Z')
            ->and($entry->bookingItem->booking->user_id)->toBe($client->id);

        $other = User::factory()->create();

        $this->withHeaders(asUser($other))
            ->postJson('/api/bookings', appointmentRequest($salon, [petFor($other, $salon['activity'])], '2026-10-06T10:30:00+02:00'))
            ->assertConflict()
            ->assertJson(['message' => __('agenda.slot_unavailable')]);
    });

    it('gives the appointment to the first free resource unless the client picks one', function () {
        $salon = appointmentSalon();
        $max = AgendaResource::factory()->scheduledIn($salon['activity'])->create(['name' => 'Max']);
        Booking::factory()->appointment($salon, '2026-10-06T10:00:00+02:00')->create();
        $client = User::factory()->create();
        $dog = petFor($client, $salon['activity']);

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', appointmentRequest($salon, [$dog], '2026-10-06T10:00:00+02:00'))
            ->assertOk()
            ->assertJsonPath('appointment.resource.id', $max->id);

        $this->postJson('/api/bookings/quote', appointmentRequest($salon, [$dog], '2026-10-06T10:00:00+02:00', ['resource_id' => $salon['resource']->id]))
            ->assertConflict();

        $this->postJson('/api/bookings/quote', appointmentRequest($salon, [$dog], '2026-10-06T11:00:00+02:00', ['resource_id' => $salon['resource']->id]))
            ->assertOk()
            ->assertJsonPath('appointment.resource.id', $salon['resource']->id);
    });

    it('refuses a start off the grid, before the notice or outside the hours', function (string $startsAt) {
        $salon = appointmentSalon();
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', appointmentRequest($salon, [petFor($client, $salon['activity'])], $startsAt))
            ->assertConflict();
    })->with([
        'off the grid' => '2026-10-06T10:10:00+02:00',
        'before the notice' => '2026-10-05T09:00:00+02:00',
        'ending after closing time' => '2026-10-06T17:30:00+02:00',
    ]);

    it('refuses a species the activity does not take in, and a resource working elsewhere', function () {
        $salon = appointmentSalon();
        $client = User::factory()->create();
        $cat = Pet::factory()->for($client)->create(['animal_type_id' => AnimalType::factory()->cat()]);
        $elsewhere = AgendaResource::factory()->for($salon['activity']->organization)->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', appointmentRequest($salon, [$cat], '2026-10-06T10:00:00+02:00'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pet_ids.0' => __('agenda.pet_not_served', ['pet' => $cat->name])]);

        $this->postJson('/api/bookings/quote', appointmentRequest($salon, [petFor($client, $salon['activity'])], '2026-10-06T10:00:00+02:00', ['resource_id' => $elsewhere->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resource_id']);
    });

    it('refuses a service the activity does not sell on appointment', function () {
        $salon = appointmentSalon();
        $salon['service']->update(['requires_scheduling' => false]);
        $client = User::factory()->create();

        $this->withHeaders(asUser($client))
            ->postJson('/api/bookings/quote', appointmentRequest($salon, [petFor($client, $salon['activity'])], '2026-10-06T10:00:00+02:00'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_id' => __('agenda.service_unavailable')]);
    });
});

describe('cancellation', function () {
    it('frees the slot of an appointment cancelled before it is accepted', function () {
        $salon = appointmentSalon();
        $booking = Booking::factory()->appointment($salon, '2026-10-06T10:00:00+02:00')->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/cancel', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'canceled']);

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('items.0.status', 'cancelled');

        expect(ResourceBooking::count())->toBe(0);
    });

    it('counts the cancellation policy from the time of the appointment', function (string $now, string $refunded) {
        $salon = appointmentSalon();
        $booking = Booking::factory()->confirmed()->appointment($salon, '2026-10-06T10:00:00+02:00')
            ->create(['cancellation_policy' => CancellationPolicyEnum::FLEXIBLE]);
        stripeRefunds($this->stripe());
        $this->travelTo(CarbonImmutable::parse($now, 'Europe/Paris'));

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('refunded_amount', $refunded);

        expect(ResourceBooking::count())->toBe(0);
    })->with([
        'more than a day before' => ['2026-10-05 09:59', '64.80'],
        'the same morning' => ['2026-10-06 08:00', '0.00'],
    ]);

    it('refuses to cancel an appointment that has begun', function () {
        $salon = appointmentSalon();
        $booking = Booking::factory()->confirmed()->appointment($salon, '2026-10-06T10:00:00+02:00')->create();
        $this->travelTo(CarbonImmutable::parse('2026-10-06 10:05', 'Europe/Paris'));

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('booking.not_cancellable')]);
    });
});

describe('lifecycle', function () {
    it('expires a request left unanswered when the appointment time comes, not at midnight', function () {
        $salon = appointmentSalon();
        $booking = Booking::factory()->appointment($salon, '2026-10-05T17:00:00+02:00')->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/cancel', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'canceled']);

        $this->artisan('bookings:expire')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatusEnum::PENDING);

        $this->travelTo(CarbonImmutable::parse('2026-10-05 17:00', 'Europe/Paris'));
        $this->artisan('bookings:expire')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatusEnum::EXPIRED)
            ->and($booking->items()->sole()->status)->toBe(BookingItemStatusEnum::CANCELLED)
            ->and(ResourceBooking::count())->toBe(0);
    });

    it('starts and completes an appointment at its hours', function () {
        $salon = appointmentSalon();
        $booking = Booking::factory()->confirmed()->appointment($salon, '2026-10-05T10:00:00+02:00')->create();

        $this->artisan('bookings:advance')->assertSuccessful();
        expect($booking->fresh()->status)->toBe(BookingStatusEnum::CONFIRMED);

        $this->travelTo(CarbonImmutable::parse('2026-10-05 10:00', 'Europe/Paris'));
        $this->artisan('bookings:advance')->assertSuccessful();
        expect($booking->fresh()->status)->toBe(BookingStatusEnum::IN_PROGRESS);

        $this->travelTo(CarbonImmutable::parse('2026-10-05 11:00', 'Europe/Paris'));
        $this->artisan('bookings:advance')->assertSuccessful();
        expect($booking->fresh()->status)->toBe(BookingStatusEnum::COMPLETED);
    });
});
