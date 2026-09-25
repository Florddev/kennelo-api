<?php

declare(strict_types=1);

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use App\Models\AnimalBreed;
use App\Models\AnimalType;
use App\Models\Pet;

it('takes the size from the pet sheet first', function () {
    $pet = Pet::factory()->for(AnimalType::factory()->dog())->create(['size_class' => PetSizeClassEnum::SMALL, 'weight' => '40.00']);

    expect($pet->effectiveSizeClass())->toBe(PetSizeClassEnum::SMALL);
});

it('deduces the size from the weight thresholds of the species', function (string $weight, PetSizeClassEnum $size) {
    $pet = Pet::factory()->for(AnimalType::factory()->dog())->create(['weight' => $weight]);

    expect($pet->effectiveSizeClass())->toBe($size);
})->with([
    ['8.00', PetSizeClassEnum::SMALL],
    ['10.00', PetSizeClassEnum::SMALL],
    ['10.01', PetSizeClassEnum::MEDIUM],
    ['45.00', PetSizeClassEnum::LARGE],
    ['62.50', PetSizeClassEnum::GIANT],
]);

it('falls back on the breed when the weight says nothing', function () {
    $rabbit = AnimalType::factory()->create(['code' => 'rabbit']);
    $breed = AnimalBreed::factory()->for($rabbit)->create([
        'default_size_class' => PetSizeClassEnum::MEDIUM,
        'default_coat_type' => PetCoatTypeEnum::LONG,
    ]);
    $pet = Pet::factory()->for($rabbit)->create(['animal_breed_id' => $breed->id, 'weight' => '3.00']);

    expect($pet->effectiveSizeClass())->toBe(PetSizeClassEnum::MEDIUM)
        ->and($pet->effectiveCoatType())->toBe(PetCoatTypeEnum::LONG);
});

it('prefers the coat of the pet sheet to the one of the breed', function () {
    $dog = AnimalType::factory()->dog()->create();
    $breed = AnimalBreed::factory()->for($dog)->create(['default_coat_type' => PetCoatTypeEnum::LONG]);
    $pet = Pet::factory()->for($dog)->create(['animal_breed_id' => $breed->id, 'coat_type' => PetCoatTypeEnum::SHORT]);

    expect($pet->effectiveCoatType())->toBe(PetCoatTypeEnum::SHORT)
        ->and(Pet::factory()->for($dog)->create()->effectiveSizeClass())->toBeNull();
});
