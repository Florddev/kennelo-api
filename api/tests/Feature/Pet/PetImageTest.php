<?php

declare(strict_types=1);

use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('owner can upload an avatar for their pet', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
    $this->withHeaders(asUser($user))
        ->postJson("/api/pets/{$pet->id}/avatar", ['avatar' => $file])
        ->assertOk()
        ->assertJsonPath('data.id', $pet->id);
    expect($pet->fresh()->getFirstMedia(MediaService::COLLECTION_AVATAR))->not->toBeNull();
});

it('uploading a new avatar replaces the old one', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $this->withHeaders(asUser($user))->postJson("/api/pets/{$pet->id}/avatar", ['avatar' => UploadedFile::fake()->image('first.jpg', 200, 200)])->assertOk();
    $firstMediaId = $pet->fresh()->getFirstMedia(MediaService::COLLECTION_AVATAR)?->id;
    $this->withHeaders(asUser($user))->postJson("/api/pets/{$pet->id}/avatar", ['avatar' => UploadedFile::fake()->image('second.jpg', 200, 200)])->assertOk();
    $freshPet = $pet->fresh();
    expect($freshPet->getMedia(MediaService::COLLECTION_AVATAR))->toHaveCount(1);
    expect($freshPet->getFirstMedia(MediaService::COLLECTION_AVATAR)?->id)->not->toBe($firstMediaId);
});

it('other user cannot upload avatar to someone else pet', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $this->withHeaders(asUser($other))->postJson("/api/pets/{$pet->id}/avatar", ['avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200)])->assertForbidden();
});

it('avatar upload rejects non-image files', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $this->withHeaders(asUser($user))->postJson("/api/pets/{$pet->id}/avatar", ['avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])->assertUnprocessable()->assertJsonValidationErrors(['avatar']);
});

it('owner can add an image to their pet', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $this->withHeaders(asUser($user))->postJson("/api/pets/{$pet->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg', 300, 300)])->assertCreated();
    expect($pet->fresh()->getMedia(MediaService::COLLECTION_IMAGES))->toHaveCount(1);
});

it('owner cannot add more than 15 images to a pet', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    for ($i = 1; $i <= 15; $i++) {
        $pet->addMedia(UploadedFile::fake()->image("photo{$i}.jpg", 300, 300))->toMediaCollection(MediaService::COLLECTION_IMAGES);
    }
    $this->withHeaders(asUser($user))->postJson("/api/pets/{$pet->id}/images", ['image' => UploadedFile::fake()->image('sixth.jpg', 300, 300)])->assertUnprocessable();
});

it('owner can list images of their pet', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $pet->addMedia(UploadedFile::fake()->image('a.jpg', 300, 300))->toMediaCollection(MediaService::COLLECTION_IMAGES);
    $pet->addMedia(UploadedFile::fake()->image('b.jpg', 300, 300))->toMediaCollection(MediaService::COLLECTION_IMAGES);
    $this->withHeaders(asUser($user))->getJson("/api/pets/{$pet->id}/images")->assertOk()->assertJsonCount(2, 'data');
});

it('owner can delete an image from their pet', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $media = $pet->addMedia(UploadedFile::fake()->image('photo.jpg', 300, 300))->toMediaCollection(MediaService::COLLECTION_IMAGES);
    $this->withHeaders(asUser($user))->deleteJson("/api/pets/{$pet->id}/images/{$media->uuid}")->assertNoContent();
    expect($pet->fresh()->getMedia(MediaService::COLLECTION_IMAGES))->toHaveCount(0);
});

it('other user cannot add images to someone else pet', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $owner->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $this->withHeaders(asUser($other))->postJson("/api/pets/{$pet->id}/images", ['image' => UploadedFile::fake()->image('photo.jpg', 300, 300)])->assertForbidden();
});

it('cannot delete an image that belongs to a different pet', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet1 = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $pet2 = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Bella']);
    $media = $pet2->addMedia(UploadedFile::fake()->image('photo.jpg', 300, 300))->toMediaCollection(MediaService::COLLECTION_IMAGES);
    $this->withHeaders(asUser($user))->deleteJson("/api/pets/{$pet1->id}/images/{$media->uuid}")->assertForbidden();
});
