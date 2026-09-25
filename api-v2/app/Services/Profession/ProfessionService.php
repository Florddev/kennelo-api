<?php

declare(strict_types=1);

namespace App\Services\Profession;

use App\Enums\LocationModeEnum;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Référentiel des métiers : lecture publique, et gestion par Kennelo dans le back-office.
 *
 * Un métier déjà exercé par des activités ne peut pas leur retirer ce qu'elles utilisent :
 * son mode de réservation, un lieu pratiqué ou une espèce acceptée.
 */
class ProfessionService
{
    /**
     * Catégories et métiers ouverts, dans l'ordre d'affichage, avec les espèces de chaque métier.
     *
     * @return Collection<int, ProfessionCategory>
     */
    public function catalog(): Collection
    {
        return ProfessionCategory::query()
            ->whereHas('professions', fn (Builder $query) => $query->active())
            ->with(['professions' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('code')->with('animalTypes')])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * @return Collection<int, ProfessionCategory>
     */
    public function categories(): Collection
    {
        return ProfessionCategory::query()->withCount('professions')->orderBy('sort_order')->orderBy('code')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): ProfessionCategory
    {
        return ProfessionCategory::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(ProfessionCategory $category, array $data): ProfessionCategory
    {
        $category->update($data);

        return $category;
    }

    public function deleteCategory(ProfessionCategory $category): void
    {
        if ($category->professions()->exists()) {
            throw ValidationException::withMessages(['category' => __('profession.category_not_empty')]);
        }

        $category->delete();
    }

    /**
     * @return Collection<int, Profession>
     */
    public function professions(): Collection
    {
        return Profession::query()
            ->with(['category', 'animalTypes', 'documentRequirements'])
            ->withCount('activities')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProfession(array $data): Profession
    {
        return DB::transaction(function () use ($data): Profession {
            $profession = new Profession($this->attributes($data));
            $profession->save();

            $profession->animalTypes()->sync($data['animal_type_ids']);
            $this->replaceDocumentRequirements($profession, $data['documents'] ?? []);

            return $profession->refresh()->load(['category', 'animalTypes', 'documentRequirements']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfession(Profession $profession, array $data): Profession
    {
        DB::transaction(function () use ($profession, $data): void {
            $profession->fill($this->attributes($data));

            $this->assertCompatibleWithActivities($profession, $data['animal_type_ids'] ?? null);

            $profession->save();

            if (isset($data['animal_type_ids'])) {
                $profession->animalTypes()->sync($data['animal_type_ids']);
            }

            if (array_key_exists('documents', $data)) {
                $this->replaceDocumentRequirements($profession, $data['documents']);
            }
        });

        return $profession->load(['category', 'animalTypes', 'documentRequirements'])->loadCount('activities');
    }

    /**
     * Les lieux arrivent sous forme de liste ; la table les range en trois booléens.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = Arr::except($data, ['locations', 'animal_type_ids', 'documents']);

        if (isset($data['locations'])) {
            foreach (LocationModeEnum::cases() as $location) {
                $attributes['allows_'.$location->value] = in_array($location->value, $data['locations'], true);
            }
        }

        return $attributes;
    }

    /**
     * @param  list<string>|null  $animalTypeIds
     */
    private function assertCompatibleWithActivities(Profession $profession, ?array $animalTypeIds): void
    {
        if ($profession->isDirty(['booking_mode', 'billing_unit']) && $profession->activities()->exists()) {
            throw ValidationException::withMessages(['booking_mode' => __('profession.mode_locked')]);
        }

        foreach (LocationModeEnum::cases() as $location) {
            if (! $profession->allowsLocation($location) && $profession->activities()->where('serves_'.$location->value, true)->exists()) {
                throw ValidationException::withMessages(['locations' => __('profession.location_in_use')]);
            }
        }

        if ($animalTypeIds === null) {
            return;
        }

        $speciesInUse = $profession->activities()
            ->whereHas('animalTypes', fn (Builder $query) => $query->whereNotIn('animal_types.id', $animalTypeIds))
            ->exists();

        if ($speciesInUse) {
            throw ValidationException::withMessages(['animal_type_ids' => __('profession.animal_type_in_use')]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     */
    private function replaceDocumentRequirements(Profession $profession, array $documents): void
    {
        $profession->documentRequirements()->delete();

        foreach ($documents as $document) {
            $profession->documentRequirements()->create([
                'document_type' => $document['type'],
                'is_required' => $document['is_required'] ?? true,
                'validity_months' => $document['validity_months'] ?? null,
            ]);
        }
    }
}
