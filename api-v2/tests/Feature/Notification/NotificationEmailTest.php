<?php

declare(strict_types=1);

use App\Enums\NotificationTypeEnum;
use App\Enums\PlanEnum;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AppNotification;
use Database\Seeders\Reference\SubscriptionPlanSeeder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Notification;

function mailOf(AppNotification $notification, User $notifiable, string $locale = 'en'): MailMessage
{
    app()->setLocale($locale);

    return $notification->toMail($notifiable);
}

function messageNotification(Message $message): AppNotification
{
    return new AppNotification(NotificationTypeEnum::NEW_MESSAGE, [
        'conversation_id' => $message->conversation_id,
        'message_id' => $message->id,
        'sender_name' => 'Marc Rolland',
        'activity_name' => 'Pension du Lac',
        'content_preview' => $message->content,
    ]);
}

dataset('mailed types', fn (): array => array_values(array_filter(
    NotificationTypeEnum::cases(),
    fn (NotificationTypeEnum $type): bool => $type->sendsEmail(),
)));

it('has a subject, a line and an action label in every language', function (NotificationTypeEnum $type) {
    foreach (['fr', 'en', 'ar'] as $locale) {
        expect(Lang::has("notification-emails.{$type->value}.subject", $locale, false))->toBeTrue()
            ->and(Lang::has("notification-emails.{$type->value}.line", $locale, false))->toBeTrue();

        if ($type->mailLink() !== null) {
            expect(Lang::has("notification-emails.actions.{$type->mailLink()}", $locale, false))->toBeTrue()
                ->and(config("notifications.links.{$type->mailLink()}"))->toBeString();
        }
    }
})->with('mailed types');

it('mails the owner when Kennelo approves the company, with a link to it', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://kennelo.test/']);
    $organization = Organization::factory()->create(['legal_name' => 'Les Pattes du Lac']);

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/organizations/{$organization->id}/approve")
        ->assertOk();

    Notification::assertSentTo($organization->owner, AppNotification::class, function (AppNotification $notification, array $channels) use ($organization): bool {
        $mail = mailOf($notification, $organization->owner);

        return in_array('mail', $channels, true)
            && $mail->subject === __('notification-emails.organization_approved.subject')
            && str_contains($mail->introLines[0], 'Les Pattes du Lac')
            && $mail->actionUrl === "https://kennelo.test/hosting/organizations/{$organization->id}";
    });
});

it('mails the invitation to join a team', function () {
    Notification::fake();
    $this->seed(SubscriptionPlanSeeder::class);
    $organization = Organization::factory()->create();
    Subscription::factory()->for($organization)->onPlan(PlanEnum::PRO)->create();
    $invitee = User::factory()->create(['email' => 'lea@example.com']);

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/organizations/{$organization->id}/members", ['email' => 'lea@example.com'])
        ->assertCreated();

    Notification::assertSentTo($invitee, AppNotification::class, fn (AppNotification $notification, array $channels): bool => in_array('mail', $channels, true)
        && str_ends_with((string) mailOf($notification, $invitee)->actionUrl, '/hosting/invitations'));
});

it('mails a banned account the reason and the end of the ban, without a link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$user->id}/ban", ['reason' => 'Annonces frauduleuses', 'banned_until' => now()->addDays(30)->toDateString()])
        ->assertOk();

    Notification::assertSentTo($user, AppNotification::class, function (AppNotification $notification, array $channels) use ($user): bool {
        $mail = mailOf($notification, $user, 'fr');

        return in_array('mail', $channels, true)
            && $mail->introLines[1] === 'Motif : Annonces frauduleuses'
            && str_starts_with($mail->introLines[2], 'Jusqu\'au ')
            && $mail->actionUrl === null;
    });
});

it('invites the client to review a completed booking', function () {
    $booking = Booking::factory()->create();
    $notification = new AppNotification(NotificationTypeEnum::BOOKING_COMPLETED, ['booking_id' => $booking->id, 'activity_name' => 'Pension du Lac']);

    expect($notification->via($booking->user))->toContain('mail')
        ->and(mailOf($notification, $booking->user)->actionUrl)->toEndWith("/bookings/{$booking->id}/review");
});

it('leaves the button out when the link lacks its data', function () {
    $user = User::factory()->create();

    expect(mailOf(new AppNotification(NotificationTypeEnum::ORGANIZATION_APPROVED), $user)->actionUrl)->toBeNull();
});

describe('new messages', function () {
    it('mails a message later, only if it is still unread and the last one of its side', function () {
        $this->freezeTime();
        $conversation = Conversation::factory()->create();
        $client = $conversation->user;
        $first = Message::factory()->for($conversation)->fromTeam(User::factory()->create()->id)->create(['created_at' => now()->subMinute()]);
        $last = Message::factory()->for($conversation)->fromTeam(User::factory()->create()->id)->create();
        Message::factory()->for($conversation)->create(['sender_id' => $client->id, 'created_at' => now()->addMinute()]);

        expect(messageNotification($last)->withDelay($client, 'mail'))->toEqual(now()->addMinutes(15))
            ->and(messageNotification($last)->withDelay($client, 'user_database'))->toBeNull()
            ->and(messageNotification($first)->shouldSend($client, 'mail'))->toBeFalse()
            ->and(messageNotification($first)->shouldSend($client, 'user_database'))->toBeTrue()
            ->and(messageNotification($last)->shouldSend($client, 'mail'))->toBeTrue();

        MessageRead::query()->insert(['message_id' => $last->id, 'user_id' => $client->id, 'read_at' => now()]);

        expect(messageNotification($last)->shouldSend($client, 'mail'))->toBeFalse();
    });

    it('links the mail to the conversation', function () {
        $message = Message::factory()->fromTeam(User::factory()->create()->id)->create(['content' => 'Rex a bien mangé ce matin.']);
        $mail = mailOf(messageNotification($message), $message->conversation->user);

        expect($mail->subject)->toContain('Marc Rolland')
            ->and($mail->introLines[0])->toContain('Rex a bien mangé ce matin.')
            ->and($mail->actionUrl)->toEndWith("/messages/{$message->conversation_id}");
    });
});
