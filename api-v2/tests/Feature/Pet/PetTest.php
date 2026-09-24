<?php

declare(strict_types=1);

use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list their own pets', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Bella']);

    $this->withHeaders(asUser($user))
        ->getJson('/api/pets')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('user only sees their own pets, not others', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    Pet::create(['user_id' => $otherUser->id, 'animal_type_id' => $animalType->id, 'name' => 'Stranger']);

    $this->withHeaders(asUser($user))
        ->getJson('/api/pets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Rex');
});

it('pets list is paginated', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    for ($i = 1; $i <= 5; $i++) {
        Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => "Pet $i"]);
    }

    $this->withHeaders(asUser($user))
        ->getJson('/api/pets')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('unauthenticated user cannot list pets', function () {
    $this->getJson('/api/pets')
        ->assertUnauthorized();
});

// ─── show ─────────────────────────────────────────────────────────────────────

it('owner can view their pet', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->getJson("/api/pets/{$pet->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $pet->id)
        ->assertJsonPath('data.name', 'Rex');
});

it('other user cannot view someone else pet', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($other))
        ->getJson("/api/pets/{$pet->id}")
        ->assertForbidden();
});

it('returns 404 for unknown pet', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/pets/99999')
        ->assertNotFound();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('authenticated user can create a pet', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/pets', [
            'animal_type_id' => $animalType->id,
            'name' => 'Rex',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Rex')
        ->assertJsonPath('data.user_id', $user->id);
});

it('created pet is automatically associated to the authenticated user', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);

    $response = $this->withHeaders(asUser($user))
        ->postJson('/api/pets', ['animal_type_id' => $animalType->id, 'name' => 'Mimi'])
        ->assertCreated();

    expect(Pet::find($response->json('data.id'))->user_id)->toBe($user->id);
});

it('creating a pet requires a name', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/pets', ['animal_type_id' => $animalType->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('creating a pet requires a valid animal_type_id', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/pets', ['animal_type_id' => 99999, 'name' => 'Rex'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['animal_type_id']);
});

it('unauthenticated user cannot create a pet', function () {
    $this->postJson('/api/pets', ['name' => 'Rex'])
        ->assertUnauthorized();
});

// ─── update ───────────────────────────────────────────────────────────────────

it('owner can update their pet', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}", ['name' => 'Max'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Max');
});

it('other user cannot update someone else pet', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($other))
        ->putJson("/api/pets/{$pet->id}", ['name' => 'Hacked'])
        ->assertForbidden();
});

it('unauthenticated user cannot update a pet', function () {
    $owner = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->putJson("/api/pets/{$pet->id}", ['name' => 'Hacked'])
        ->assertUnauthorized();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('owner can delete their pet', function () {
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/pets/{$pet->id}")
        ->assertNoContent();

    expect(Pet::find($pet->id))->toBeNull();
});

it('other user cannot delete someone else pet', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($other))
        ->deleteJson("/api/pets/{$pet->id}")
        ->assertForbidden();

    expect(Pet::find($pet->id))->not->toBeNull();
});

it('unauthenticated user cannot delete a pet', function () {
    $owner = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->deleteJson("/api/pets/{$pet->id}")
        ->assertUnauthorized();
});
