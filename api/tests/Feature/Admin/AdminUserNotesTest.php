<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserNote;

it('admin can create a note on a user', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/notes", ['body' => 'Suspicious activity'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Suspicious activity');

    expect(UserNote::where('user_id', $target->id)->where('author_id', $admin->id)->exists())->toBeTrue();
});

it('admin can list notes for a user', function () {
    $admin = adminUser();
    $target = User::factory()->create();
    UserNote::create(['user_id' => $target->id, 'author_id' => $admin->id, 'body' => 'a']);
    UserNote::create(['user_id' => $target->id, 'author_id' => $admin->id, 'body' => 'b']);

    $this->withHeaders(asUser($admin))
        ->getJson("/api/admin/users/{$target->id}/notes")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('admin can update a note', function () {
    $admin = adminUser();
    $target = User::factory()->create();
    $note = UserNote::create(['user_id' => $target->id, 'author_id' => $admin->id, 'body' => 'old']);

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/users/{$target->id}/notes/{$note->id}", ['body' => 'new'])
        ->assertOk()
        ->assertJsonPath('data.body', 'new');
});

it('admin can delete a note', function () {
    $admin = adminUser();
    $target = User::factory()->create();
    $note = UserNote::create(['user_id' => $target->id, 'author_id' => $admin->id, 'body' => 'x']);

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/admin/users/{$target->id}/notes/{$note->id}")
        ->assertOk();

    expect(UserNote::find($note->id))->toBeNull();
});

it('returns 404 when the note belongs to another user', function () {
    $admin = adminUser();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $note = UserNote::create(['user_id' => $a->id, 'author_id' => $admin->id, 'body' => 'x']);

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/users/{$b->id}/notes/{$note->id}", ['body' => 'y'])
        ->assertNotFound();
});

it('note body is required', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/notes", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

it('forbids non-admin from managing notes', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/admin/users/{$target->id}/notes")
        ->assertForbidden();
});
