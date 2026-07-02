<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\Booking;
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
