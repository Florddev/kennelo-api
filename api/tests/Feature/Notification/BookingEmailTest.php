<?php

declare(strict_types=1);

use App\Enums\NotificationTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

it('sends email-worthy notifications on the mail channel', function () {
    $user = User::factory()->create();

    $channels = (new AppNotification(NotificationTypeEnum::BOOKING_CONFIRMED))->via($user);

    expect($channels)->toContain('mail')->toContain('user_database');
});

it('does not send email for non email notifications', function () {
    $user = User::factory()->create();

    $channels = (new AppNotification(NotificationTypeEnum::NEW_MESSAGE))->via($user);

    expect($channels)->not->toContain('mail');
});

it('builds the mail in the recipient locale', function () {
    $user = User::factory()->create();

    App::setLocale('fr');
    $mail = (new AppNotification(NotificationTypeEnum::BOOKING_CONFIRMED, ['activity_name' => 'Chez Rex']))
        ->toMail($user);

    expect($mail->subject)->toBe('Réservation confirmée');
});

it('emails the client when a booking is confirmed', function () {
    NotificationFacade::fake();
    app()->instance(StripeClient::class, new FakeStripeClient);

    $client = User::factory()->create();
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'user_id' => $client->id,
        'activity_id' => $activity->id,
        'stripe_payment_intent_id' => 'pi_email_confirm',
        'payment_status' => 'requires_capture',
    ]);

    app(BookingService::class)->confirm($booking, $manager);

    NotificationFacade::assertSentTo(
        $client,
        AppNotification::class,
        fn (AppNotification $notification, array $channels): bool => in_array('mail', $channels, true),
    );
});
