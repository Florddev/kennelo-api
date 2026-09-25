<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\ServiceOfferEnum;
use App\Models\Activity;
use App\Models\Pet;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ce qu'une activité vend du catalogue de son entreprise, et à quelles conditions : seule ou en option d'un séjour,
 * avec un ajustement de prix, incluse ou non dans le prix du séjour.
 */
class ActivityOfferService
{
    /**
     * Valeurs d'une offre qui ne les précise pas.
     */
    private const array DEFAULTS = [
        'offered_as' => ServiceOfferEnum::STANDALONE,
        'adjustment_percent' => '0',
        'is_included' => false,
        'is_active' => true,
    ];

    public function __construct(
        private readonly ServicePriceResolver $prices,
    ) {}

    /**
     * Ce qu'un client peut réserver ; avec $withInactive, aussi ce qui est retiré de la vente (vue de l'équipe).
     * Avec un animal, chaque prestation porte en plus son prix et sa durée pour lui (pet_price),
     * ou null si la grille ne prévoit rien pour cet animal.
     *
     * @return Collection<int, Service>
     */
    public function offers(Activity $activity, ?Pet $pet = null, bool $withInactive = false): Collection
    {
        $query = $activity->services();

        // wherePivot() n'existe que sur la relation : pas de when(), qui passerait le builder Eloquent.
        if (! $withInactive) {
            $query->wherePivot('is_active', true)->where('services.is_active', true);
        }

        $services = $query->with('prices')->orderBy('services.name')->orderBy('services.id')->get();

        if ($pet !== null) {
            $pet->loadMissing(['animalType', 'animalBreed']);

            $services->each(fn (Service $service) => $service->setAttribute('pet_price', $this->priceForPet($service, $pet)));
        }

        return $services;
    }

    /**
     * Crée ou remplace l'offre de la prestation dans l'activité.
     *
     * @param  array<string, mixed>  $data
     */
    public function offer(Activity $activity, Service $service, array $data): Service
    {
        $activity->services()->syncWithoutDetaching([
            $service->id => [...self::DEFAULTS, ...$data, 'organization_id' => $activity->organization_id],
        ]);

        return $activity->services()->with('prices')->findOrFail($service->id);
    }

    public function withdraw(Activity $activity, Service $service): void
    {
        $activity->services()->detach($service->id);
    }

    /**
     * @return array{price: numeric-string, duration_minutes: int|null}|null
     */
    private function priceForPet(Service $service, Pet $pet): ?array
    {
        $line = $this->prices->forPet($service, $pet);

        if ($line === null) {
            return null;
        }

        return [
            'price' => Money::adjust($line->price, $service->offer->adjustment_percent),
            'duration_minutes' => $line->duration_minutes,
        ];
    }
}
