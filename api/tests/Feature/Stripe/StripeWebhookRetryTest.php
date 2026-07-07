<?php

declare(strict_types=1);

use App\Models\StripeEvent;
use App\Services\Finance\FinancialJournalService;
use App\Services\Notification\NotificationService;
use App\Services\Stripe\StripeWebhookService;
use App\Services\Subscription\SubscriptionWebhookService;
use Tests\Support\ThrowOnceWebhookService;

it('keeps an event reprocessable when the handler throws', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $event = [
        'id' => 'evt_retry_1',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_retry_1', 'object' => 'payment_intent']],
    ];

    $post = function () use ($event) {
        $payload = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        return $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}"],
            $payload,
        );
    };

    $service = new ThrowOnceWebhookService(
        app(NotificationService::class),
        app(FinancialJournalService::class),
        app(SubscriptionWebhookService::class),
    );
    app()->instance(StripeWebhookService::class, $service);

    try {
        $post->call($this);
    } catch (RuntimeException) {
    }

    $record = StripeEvent::find('evt_retry_1');
    expect($record)->not->toBeNull();
    expect($record->processed_at)->toBeNull();

    $post->call($this)->assertOk();

    expect(StripeEvent::find('evt_retry_1')->processed_at)->not->toBeNull();
    expect($service->calls)->toBe(2);
});
