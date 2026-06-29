<?php

declare(strict_types=1);

use App\Models\AnimalBreed;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('unauthenticated user cannot list animal breeds', function () {
    $this->getJson('/api/animal-breeds')
        ->assertUnauthorized();
});

it('authenticated user can list all animal breeds', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $cat = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);

    AnimalBreed::create(['animal_type_id' => $dog->id, 'breed' => 'labrador_retriever', 'label' => 'Labrador Retriever']);
    AnimalBreed::create(['animal_type_id' => $cat->id, 'breed' => 'siamese', 'label' => 'Siamese']);

    $this->withHeaders(asUser($user))
        ->getJson('/api/animal-breeds')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'animal_type_id', 'breed', 'label']]]);
});

it('filters breeds by animal_type_id', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $cat = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);

    AnimalBreed::create(['animal_type_id' => $dog->id, 'breed' => 'labrador_retriever', 'label' => 'Labrador Retriever']);
    AnimalBreed::create(['animal_type_id' => $cat->id, 'breed' => 'siamese', 'label' => 'Siamese']);

    $this->withHeaders(asUser($user))
        ->getJson("/api/animal-breeds?animal_type_id={$dog->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.breed', 'labrador_retriever');
});

it('rejects an invalid animal_type_id filter', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/animal-breeds?animal_type_id=99999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['animal_type_id']);
});

it('orders breeds by their localized label', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    AnimalBreed::create(['animal_type_id' => $dog->id, 'breed' => 'zzz_poodle', 'label' => 'Caniche']);
    AnimalBreed::create(['animal_type_id' => $dog->id, 'breed' => 'aaa_bulldog', 'label' => 'Bouledogue']);

    $this->withHeaders(asUser($user))
        ->getJson('/api/animal-breeds')
        ->assertOk()
        ->assertJsonPath('data.0.label', 'Bouledogue')
        ->assertJsonPath('data.1.label', 'Caniche');
});

it('returns empty list when no breeds exist', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/animal-breeds')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ─── store ────────────────────────────────────────────────────────────────────

it('can create a pet with a breed matching its animal type', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $breed = AnimalBreed::create(['animal_type_id' => $dog->id, 'breed' => 'labrador_retriever', 'label' => 'Labrador Retriever']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/pets', [
            'animal_type_id' => $dog->id,
            'animal_breed_id' => $breed->id,
            'name' => 'Rex',
        ])
        ->assertCreated()
        ->assertJsonPath('data.animal_breed_id', $breed->id);
});

it('rejects creating a pet with a breed from another animal type', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $cat = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);
    $catBreed = AnimalBreed::create(['animal_type_id' => $cat->id, 'breed' => 'siamese', 'label' => 'Siamese']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/pets', [
            'animal_type_id' => $dog->id,
            'animal_breed_id' => $catBreed->id,
            'name' => 'Rex',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['animal_breed_id']);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('can update a pet with a breed matching its current animal type', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $breed = AnimalBreed::create(['animal_type_id' => $dog->id, 'breed' => 'labrador_retriever', 'label' => 'Labrador Retriever']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $dog->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}", ['animal_breed_id' => $breed->id])
        ->assertOk()
        ->assertJsonPath('data.animal_breed_id', $breed->id);
});

it('rejects updating a pet with a breed from another animal type', function () {
    $user = User::factory()->create();
    $dog = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $cat = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);
    $catBreed = AnimalBreed::create(['animal_type_id' => $cat->id, 'breed' => 'siamese', 'label' => 'Siamese']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $dog->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}", ['animal_breed_id' => $catBreed->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['animal_breed_id']);
});
