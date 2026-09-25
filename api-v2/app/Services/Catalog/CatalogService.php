<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Organization;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Catalogue de l'entreprise : ses prestations et forfaits, et la grille de prix de chacun.
 * Chaque activité choisit ensuite ce qu'elle vend (App\Services\Catalog\ActivityOfferService).
 */
class CatalogService
{
    public const array RELATIONS = ['prices', 'packageItems'];

    /**
     * @return Collection<int, Service>
     */
    public function services(Organization $organization): Collection
    {
        return $organization->services()->with(self::RELATIONS)->orderBy('name')->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): Service
    {
        $service = $organization->services()->create($data);

        // Rechargée pour lire les valeurs par défaut posées par la base.
        return $service->refresh()->load(self::RELATIONS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->load(self::RELATIONS);
    }

    /**
     * Suppression logique : une prestation déjà vendue reste lisible dans les réservations.
     */
    public function delete(Service $service): void
    {
        if ($service->packages()->exists()) {
            throw ValidationException::withMessages(['service' => __('catalog.service_in_package')]);
        }

        $service->delete();
    }

    /**
     * Remplace la grille d'un bloc.
     *
     * @param  list<array<string, mixed>>  $prices
     */
    public function replacePrices(Service $service, array $prices): Service
    {
        DB::transaction(function () use ($service, $prices): void {
            $service->prices()->delete();
            $service->prices()->createMany($prices);
        });

        return $service->load(self::RELATIONS);
    }

    /**
     * Remplace la composition d'un forfait d'un bloc.
     *
     * @param  list<string>  $serviceIds
     */
    public function replacePackageItems(Service $package, array $serviceIds): Service
    {
        if (! $package->is_package) {
            throw ValidationException::withMessages(['service_ids' => __('catalog.not_a_package')]);
        }

        $package->packageItems()->sync($serviceIds);

        return $package->load(self::RELATIONS);
    }
}
