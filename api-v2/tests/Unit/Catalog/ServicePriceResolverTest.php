<?php

declare(strict_types=1);

use App\Enums\PetCoatTypeEnum as Coat;
use App\Enums\PetSizeClassEnum as Size;
use App\Models\ServicePrice;
use App\Services\Catalog\ServicePriceResolver;

const DOG = 'dog-id';
const CAT = 'cat-id';
const POODLE = 'poodle-id';

/**
 * Grille d'un toilettage : une ligne par niveau de précision, plus une ligne pour les chats.
 *
 * @return list<ServicePrice>
 */
function groomingGrid(): array
{
    return [
        new ServicePrice(['animal_type_id' => DOG, 'price' => '30.00']),
        new ServicePrice(['animal_type_id' => DOG, 'size_class' => Size::LARGE, 'price' => '45.00']),
        new ServicePrice(['animal_type_id' => DOG, 'size_class' => Size::LARGE, 'coat_type' => Coat::LONG, 'price' => '55.00']),
        new ServicePrice(['animal_type_id' => DOG, 'animal_breed_id' => POODLE, 'price' => '65.00']),
        new ServicePrice(['animal_type_id' => CAT, 'price' => '25.00']),
    ];
}

function priceFor(string $species, ?string $breed = null, ?Size $size = null, ?Coat $coat = null): ?string
{
    return (new ServicePriceResolver)->resolve(groomingGrid(), $species, $breed, $size, $coat)?->price;
}

it('prefers the line of the breed', function () {
    expect(priceFor(DOG, POODLE, Size::LARGE, Coat::LONG))->toBe('65.00');
});

it('then the line of the size and the coat', function () {
    expect(priceFor(DOG, 'other-breed', Size::LARGE, Coat::LONG))->toBe('55.00');
});

it('then the line of the size alone', function () {
    expect(priceFor(DOG, null, Size::LARGE, Coat::SHORT))->toBe('45.00')
        ->and(priceFor(DOG, null, Size::LARGE))->toBe('45.00');
});

it('then the line of the species', function () {
    expect(priceFor(DOG, null, Size::SMALL, Coat::LONG))->toBe('30.00')
        ->and(priceFor(DOG))->toBe('30.00');
});

it('never uses the line of another species', function () {
    expect(priceFor(CAT, null, Size::LARGE, Coat::LONG))->toBe('25.00')
        ->and(priceFor('rabbit-id'))->toBeNull();
});

it('finds nothing when the grid has no line for the species alone and nothing more precise matches', function () {
    $grid = [new ServicePrice(['animal_type_id' => DOG, 'size_class' => Size::GIANT, 'price' => '80.00'])];

    expect((new ServicePriceResolver)->resolve($grid, DOG, null, Size::SMALL, null))->toBeNull();
});
