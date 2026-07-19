<?php

declare(strict_types=1);

use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Booking;
use App\Models\FinancialOperation;
use App\Models\StripeEvent;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

it('processes a stripe event only once', function () {
    NotificationFacade::fake();
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $client = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $client->id,
        'payment_status' => PaymentStatusEnum::REQUIRES_CAPTURE,
    ]);

    $event = [
        'id' => 'evt_idempotency_1',
        'object' => 'event',
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_idempotency_1',
                'object' => 'payment_intent',
                'status' => 'requires_payment_method',
                'metadata' => ['booking_id' => $booking->id],
            ],
        ],
    ];

    $post = function () use ($event): void {
        $payload = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}"],
            $payload,
        )->assertOk();
    };

    $post();
    $post();

    expect($booking->fresh()->payment_status)->toBe(PaymentStatusEnum::FAILED);
    expect(StripeEvent::count())->toBe(1);

    NotificationFacade::assertSentToTimes($client, AppNotification::class, 1);
});

it('records a refund only once for a duplicated charge.refunded event', function () {
    NotificationFacade::fake();
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $client = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $client->id,
        'payment_status' => PaymentStatusEnum::SUCCEEDED,
        'stripe_charge_id' => 'ch_refund_1',
    ]);

    $event = [
        'id' => 'evt_refund_1',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_refund_1',
                'object' => 'charge',
                'amount_refunded' => 5000,
                'refunds' => ['data' => [['id' => 're_refund_1']]],
            ],
        ],
    ];

    $post = function () use ($event): void {
        $payload = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}"],
            $payload,
        )->assertOk();
    };

    $post();
    $post();

    expect($booking->fresh()->payment_status)->toBe(PaymentStatusEnum::REFUNDED);
    expect(StripeEvent::count())->toBe(1);
    expect(FinancialOperation::where('type', FinancialOperationTypeEnum::REFUND)->count())->toBe(1);
});
