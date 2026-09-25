<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use App\Models\Pet;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Support\Collection;

/**
 * Choisit la ligne de la grille d'une prestation qui s'applique à un animal. Parmi les lignes de son espèce,
 * la plus précise l'emporte : sa race, puis sa taille et son poil, puis sa taille, puis l'espèce seule.
 */
final class ServicePriceResolver
{
    public function forPet(Service $service, Pet $pet): ?ServicePrice
    {
        return $this->resolve(
            $service->prices,
            $pet->animal_type_id,
            $pet->animal_breed_id,
            $pet->effectiveSizeClass(),
            $pet->effectiveCoatType(),
        );
    }

    /**
     * @param  iterable<ServicePrice>  $lines
     */
    public function resolve(iterable $lines, string $animalTypeId, ?string $breedId, ?PetSizeClassEnum $size, ?PetCoatTypeEnum $coat): ?ServicePrice
    {
        /** @var Collection<int, ServicePrice> $candidates */
        $candidates = collect($lines)->filter(fn (ServicePrice $line): bool => $line->animal_type_id === $animalTypeId);

        $precedence = [
            fn (ServicePrice $line): bool => $breedId !== null && $line->animal_breed_id === $breedId,
            fn (ServicePrice $line): bool => $line->animal_breed_id === null && $size !== null && $coat !== null
                && $line->size_class === $size && $line->coat_type === $coat,
            fn (ServicePrice $line): bool => $line->animal_breed_id === null && $line->coat_type === null
                && $size !== null && $line->size_class === $size,
            fn (ServicePrice $line): bool => $line->animal_breed_id === null && $line->size_class === null && $line->coat_type === null,
        ];

        foreach ($precedence as $matches) {
            $line = $candidates->first($matches);

            if ($line !== null) {
                return $line;
            }
        }

        return null;
    }
}
