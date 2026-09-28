<?php

declare(strict_types=1);

use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationRoleEnum;
use App\Events\Conversation\MessageSent;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageFile;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(MessageFile::DISK);
});

describe('sending', function () {
    it('sends the message of a client to the team that replies to clients', function () {
        Event::fake([MessageSent::class]);
        Notification::fake();
        $conversation = Conversation::factory()->create();
        $organization = $conversation->activity->organization;
        $employee = memberOf($organization, OrganizationRoleEnum::EMPLOYEE, $conversation->activity_id);
        $accountant = memberOf($organization, OrganizationRoleEnum::ACCOUNTANT);

        $this->travel(1)->minutes();
        $message = $this->withHeaders(asUser($conversation->user))
            ->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Bonjour, Rex peut-il venir avec son panier ?'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.sender_type', 'user')
            ->assertJsonPath('data.sender.id', $conversation->user_id)
            ->assertJsonPath('data.content', 'Bonjour, Rex peut-il venir avec son panier ?')
            ->json('data');

        expect($conversation->refresh()->last_message_at?->toISOString())->toBe($message['created_at']);
        Event::assertDispatched(MessageSent::class, fn (MessageSent $event): bool => $event->message->id === $message['id']);
        Notification::assertSentTo([$organization->owner, $employee], AppNotification::class, fn (AppNotification $notification, array $channels, User $user): bool => $notification->toUserDatabase($user)['type'] === NotificationTypeEnum::NEW_MESSAGE->value);
        Notification::assertNotSentTo([$accountant, $conversation->user], AppNotification::class);
    });

    it('sends the reply of a member to the client', function () {
        Notification::fake();
        $conversation = Conversation::factory()->create();
        $owner = $conversation->activity->organization->owner;

        $this->withHeaders(asUser($owner))
            ->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Bien sûr !'])
            ->assertCreated()
            ->assertJsonPath('data.sender_type', 'activity');

        Notification::assertSentTo($conversation->user, AppNotification::class);
        Notification::assertNotSentTo($owner, AppNotification::class);
    });

    it('refers a message to a booking of the client in this activity', function () {
        $conversation = Conversation::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $conversation->user_id, 'activity_id' => $conversation->activity_id]);
        $other = Booking::factory()->create(['user_id' => $conversation->user_id]);

        $this->withHeaders(asUser($conversation->user))
            ->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'À propos de ma réservation', 'booking_id' => $booking->id])
            ->assertCreated()
            ->assertJsonPath('data.type', 'booking_reference')
            ->assertJsonPath('data.booking_id', $booking->id);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Et celle-ci ?', 'booking_id' => $other->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking_id');
    });

    it('needs a text or a file', function () {
        $conversation = Conversation::factory()->create();

        $this->withHeaders(asUser($conversation->user))
            ->postJson("/api/conversations/{$conversation->id}/messages", ['content' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content' => __('conversations.errors.empty_message')]);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => str_repeat('a', 5001)])
            ->assertJsonValidationErrors('content');
    });

    it('keeps a conversation from the others', function () {
        $conversation = Conversation::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Bonjour'])
            ->assertNotFound();
        $this->withHeaders(asUser(memberOf($conversation->activity->organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Bonjour'])
            ->assertForbidden();

        expect(Message::query()->exists())->toBeFalse();
    });
});

describe('attachments', function () {
    it('stores the files on the private disk and serves them to the conversation only', function () {
        $conversation = Conversation::factory()->create();

        $message = $this->withHeaders(asUser($conversation->user))
            ->post("/api/conversations/{$conversation->id}/messages", [
                'files' => [
                    UploadedFile::fake()->image('../../vaccins.jpg'),
                    UploadedFile::fake()->create('ordonnance.pdf', 120, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'file')
            ->assertJsonPath('data.files.*.file_name', ['vaccins.jpg', 'ordonnance.pdf'])
            ->json('data');

        $file = MessageFile::query()->where('file_name', 'vaccins.jpg')->sole();
        Storage::disk(MessageFile::DISK)->assertExists($file->file_path);
        expect($file->file_path)->toStartWith("conversations/{$conversation->id}/");

        $this->withHeaders(asUser($conversation->activity->organization->owner))
            ->get($message['files'][0]['url'])
            ->assertOk()
            ->assertDownload('vaccins.jpg');
        $this->withHeaders(asUser(User::factory()->create()))
            ->get($message['files'][0]['url'])
            ->assertNotFound();
        // Une pièce jointe d'une autre conversation ne se lit pas par celle-ci.
        $elsewhere = Message::factory()->create()->files()->create(['file_name' => 'autre.pdf', 'file_path' => 'conversations/x/autre.pdf', 'file_type' => 'pdf', 'file_size' => 10, 'mime_type' => 'application/pdf']);
        $this->withHeaders(asUser($conversation->user))
            ->getJson("/api/conversations/{$conversation->id}/files/{$elsewhere->id}")
            ->assertNotFound();
    });

    it('refuses a file of an unexpected type', function () {
        $conversation = Conversation::factory()->create();

        $this->withHeaders(asUser($conversation->user))
            ->post("/api/conversations/{$conversation->id}/messages", [
                'files' => [UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload')],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files.0');

        expect(Storage::disk(MessageFile::DISK)->allFiles())->toBe([]);
    });
});

it('lists the messages of a conversation, the most recent first, by booking', function () {
    $conversation = Conversation::factory()->create();
    $booking = Booking::factory()->create(['user_id' => $conversation->user_id, 'activity_id' => $conversation->activity_id]);
    Message::factory()->for($conversation)->create(['created_at' => now()->subHour()]);
    $second = Message::factory()->for($conversation)->create(['booking_id' => $booking->id]);

    $this->withHeaders(asUser($conversation->user))
        ->getJson("/api/conversations/{$conversation->id}/messages?per_page=1")
        ->assertOk()
        ->assertJsonPath('data.*.id', [$second->id])
        ->assertJsonPath('meta.total', 2);
    $this->getJson("/api/conversations/{$conversation->id}/messages?booking_id={$booking->id}")
        ->assertJsonPath('data.*.id', [$second->id]);
    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson("/api/conversations/{$conversation->id}/messages")
        ->assertNotFound();
});
