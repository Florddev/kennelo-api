<?php

declare(strict_types=1);

use App\Events\MessageSent;
use App\Events\NewMessageNotification;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

it('stores conversation attachments on the private disk and returns a signed url', function () {
    Event::fake([MessageSent::class, NewMessageNotification::class]);
    Storage::fake('local');

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $file = UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf');

    $response = $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'See attached',
            'files' => [$file],
        ])
        ->assertCreated();

    expect(Storage::disk('local')->allFiles('conversations'))->toHaveCount(1);

    $fileUrl = $response->json('data.files.0.file_url');
    expect($fileUrl)->toContain('/attachments/');
    expect($fileUrl)->toContain('signature=');
});

it('rejects an unsigned attachment download', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $file = UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'See attached',
            'files' => [$file],
        ])->assertCreated();

    $message = Message::where('conversation_id', $conversation->id)->firstOrFail();
    $messageFile = MessageFile::where('message_id', $message->id)->firstOrFail();

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}/attachments/{$messageFile->id}")
        ->assertForbidden();
});
