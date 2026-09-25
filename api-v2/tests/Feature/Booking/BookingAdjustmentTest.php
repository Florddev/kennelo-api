<?php

declare(strict_types=1);

use App\Enums\BookingItemStatusEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Models\ActivityUnitType;
use App\Models\Booking;
use App\Models\BookingRefund;
use App\Models\Pet;
use App\Models\Service;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Support\FakeStripe;

/**
 * Séjour confirmé d'un chien dans une place de la pension, avec une option de bain à 20 € que l'activité vend.
 *
 * @return array{Booking, Pet, Service}
 */
function confirmedStayWithBath(): array
{
    $unitType = dogBoarding();
    $booking = Booking::factory()->confirmed()->occupying($unitType)->create();
    $dog = dogOf($booking->user, $unitType);
    $booking->pets()->attach($dog->id, ['booking_unit_id' => $booking->units()->sole()->id]);

    return [$booking, $dog, stayOption($unitType, '20.00')];
}

/**
 * La carte enregistrée au paiement initial est débitée hors session.
 */
function stripeChargesSupplement(FakeStripe $stripe, int $status = 200, array $response = []): FakeStripe
{
    return $stripe
        ->fake('get', '/v1/payment_intents/*', ['object' => 'payment_intent', 'id' => 'pi_initial', 'payment_method' => 'pm_saved', 'client_secret' => 'pi_supplement_secret'])
        ->fake('post', '/v1/payment_intents', $response ?: ['object' => 'payment_intent', 'id' => 'pi_supplement', 'status' => 'succeeded', 'latest_charge' => 'ch_supplement'], $status);
}

describe('adding an option', function () {
    it('charges it off session and adds it to the booking', function () {
        Notification::fake();
        [$booking, $dog, $bath] = confirmedStayWithBath();
        stripeChargesSupplement($this->stripe());

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items", ['service_id' => $bath->id, 'pet_id' => $dog->id])
            ->assertOk()
            ->assertJsonPath('data.items.0.status', 'to_schedule')
            ->assertJsonPath('data.payments.1.kind', 'supplement')
            ->assertJsonPath('data.payments.1.amount', '21.60')
            ->assertJsonPath('data.total_price', '86.40');

        $booking->refresh();

        expect($booking->service_fee)->toBe('6.40')
            ->and($booking->platform_fee)->toBe('6.40')
            ->and($booking->activity_amount)->toBe('73.60');
        $this->stripe()->assertSent('post', '/v1/payment_intents', fn (array $params): bool => $params['amount'] === 2160
            && $params['payment_method'] === 'pm_saved'
            && $params['off_session'] === 'true');
        Notification::assertSentTo($booking->user, AppNotification::class);
    });

    it('asks the client to confirm when the bank requires it', function () {
        Notification::fake();
        [$booking, $dog, $bath] = confirmedStayWithBath();
        stripeChargesSupplement($this->stripe(), 402, ['error' => [
            'type' => 'card_error',
            'code' => 'authentication_required',
            'message' => 'Authentication required',
            'payment_intent' => ['object' => 'payment_intent', 'id' => 'pi_supplement', 'status' => 'requires_payment_method'],
        ]]);

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items", ['service_id' => $bath->id, 'pet_id' => $dog->id])
            ->assertOk()
            ->assertJsonPath('data.payments.1.status', 'requires_action')
            ->assertJsonPath('data.total_price', '64.80');

        Notification::assertSentTo($booking->user, AppNotification::class);
        $payment = $booking->payments()->where('kind', PaymentKindEnum::SUPPLEMENT)->sole();

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/payments/{$payment->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.client_secret', 'pi_supplement_secret');
    });

    it('adds nothing when the card is declined', function () {
        [$booking, $dog, $bath] = confirmedStayWithBath();
        stripeChargesSupplement($this->stripe(), 402, ['error' => ['type' => 'card_error', 'code' => 'card_declined', 'message' => 'Declined']]);

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items", ['service_id' => $bath->id, 'pet_id' => $dog->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment' => __('booking.supplement_declined')]);

        expect($booking->items()->count())->toBe(0);
    });

    it('only adjusts an accepted booking, for a pet of the booking', function () {
        [$booking, $dog, $bath] = confirmedStayWithBath();
        $pending = Booking::factory()->create(['activity_id' => $booking->activity_id, 'organization_id' => $booking->organization_id]);
        $pending->pets()->attach($dog->id);
        $headers = asUser($booking->organization->owner);

        $this->withHeaders($headers)
            ->postJson("/api/activities/{$pending->activity_id}/bookings/{$pending->id}/items", ['service_id' => $bath->id, 'pet_id' => $dog->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('booking.not_adjustable')]);

        $this->withHeaders($headers)
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items", ['service_id' => $bath->id, 'pet_id' => Pet::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pet_id']);
    });
});

describe('removing an option', function () {
    it('refunds an option paid with the initial payment, with its share of the Kennelo fee', function () {
        Notification::fake();
        $unitType = dogBoarding();
        $booking = Booking::factory()->confirmed()->occupying($unitType)->create([
            'total_price' => '86.40',
            'service_fee' => '6.40',
            'platform_fee' => '6.40',
            'activity_amount' => '73.60',
        ]);
        $bath = $booking->items()->create([
            'service_id' => stayOption($unitType, '20.00')->id,
            'status' => BookingItemStatusEnum::TO_SCHEDULE,
            'unit_price' => '20.00',
            'subtotal' => '20.00',
            'booking_payment_id' => $booking->payments()->sole()->id,
        ]);
        $this->stripe()->fake('post', '/v1/refunds', ['object' => 'refund', 'id' => 're_bath', 'status' => 'succeeded']);

        $this->withHeaders(asUser($booking->organization->owner))
            ->deleteJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items/{$bath->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'partially_refunded')
            ->assertJsonPath('data.total_price', '64.80');

        $refund = BookingRefund::sole();
        $booking->refresh();

        expect($refund->amount)->toBe('21.60')
            ->and($refund->service_fee_amount)->toBe('1.60')
            ->and($refund->reason)->toBe(RefundReasonEnum::ADJUSTMENT)
            ->and($booking->platform_fee)->toBe('4.80')
            ->and($booking->activity_amount)->toBe('55.20');
        Notification::assertSentTo($booking->user, AppNotification::class);
    });

    it('drops a supplement the client never confirmed, without refund', function () {
        [$booking, $dog, $bath] = confirmedStayWithBath();
        $payment = $booking->payments()->create([
            'kind' => PaymentKindEnum::SUPPLEMENT,
            'amount' => '21.60',
            'currency' => 'EUR',
            'status' => PaymentStatusEnum::REQUIRES_ACTION,
            'stripe_payment_intent_id' => 'pi_waiting',
        ]);
        $item = $booking->items()->create([
            'service_id' => $bath->id,
            'pet_id' => $dog->id,
            'status' => BookingItemStatusEnum::TO_SCHEDULE,
            'unit_price' => '20.00',
            'subtotal' => '20.00',
            'booking_payment_id' => $payment->id,
        ]);
        $this->stripe()->fake('post', '/v1/payment_intents/pi_waiting/cancel', ['object' => 'payment_intent', 'id' => 'pi_waiting', 'status' => 'canceled']);

        $this->withHeaders(asUser($booking->organization->owner))
            ->deleteJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.total_price', '64.80');

        expect($payment->fresh()->status)->toBe(PaymentStatusEnum::CANCELED)
            ->and(BookingRefund::count())->toBe(0);
    });
});

it('keeps the financial journal of the booking for the team', function () {
    $booking = Booking::factory()->create();
    $this->stripe()->fake('post', '/v1/payment_intents/*/capture', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'succeeded', 'latest_charge' => 'ch_1']);
    $headers = asUser($booking->organization->owner);

    $this->withHeaders($headers)->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/confirm")->assertOk();

    $this->withHeaders($headers)
        ->getJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/operations")
        ->assertOk()
        ->assertJsonPath('data.*.type', ['capture', 'status_change'])
        ->assertJsonPath('data.0.amount', '64.80')
        ->assertJsonPath('data.1.metadata', ['status' => 'confirmed']);
});

it('keeps a booked unit, and a booked pet', function () {
    $unitType = dogBoarding();
    $booking = Booking::factory()->occupying($unitType)->create();
    $dog = dogOf($booking->user, $unitType);
    $booking->pets()->attach($dog->id);

    $this->withHeaders(asUser($unitType->activity->organization->owner))
        ->deleteJson("/api/activities/{$unitType->activity_id}/unit-types/{$unitType->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['unit_type' => __('booking.unit_type_in_use')]);

    $this->withHeaders(asUser($booking->user))
        ->deleteJson("/api/pets/{$dog->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pet' => __('booking.pet_has_bookings')]);

    expect(ActivityUnitType::whereKey($unitType->id)->exists())->toBeTrue();
});
