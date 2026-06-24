<?php

declare(strict_types=1);

namespace App\Services\Pet;

use App\Models\AnimalType;
use App\Models\AttributeDefinition;
use App\Models\Pet;
use App\Models\PetAttribute;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PetService
{
    public function getUserPets(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Pet::with(['animalType', 'animalBreed', 'media'])
            ->forUser((string) $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): Pet
    {
        return Pet::with(['animalType', 'animalBreed', 'petAttributes.attributeDefinition', 'petAttributes.attributeOption', 'media'])->findOrFail($id);
    }

    public function create(User $user, array $data): Pet
    {
        return tap(Pet::create([...$data, 'user_id' => $user->id]))->load(['animalType', 'animalBreed']);
    }

    public function update(Pet $pet, array $data): Pet
    {
        return tap($pet, fn (Pet $p) => $p->update($data))->fresh(['animalType', 'animalBreed']);
    }

    public function delete(Pet $pet): void
    {
        $pet->delete();
    }

    public function uploadAvatar(Pet $pet, UploadedFile $avatar): Pet
    {
        $pet->addMedia($avatar)
            ->toMediaCollection(MediaService::COLLECTION_AVATAR);

        return $pet->fresh(['animalType', 'media']);
    }

    public function addImage(Pet $pet, UploadedFile $image): Media
    {
        return $pet->addMedia($image)
            ->toMediaCollection(MediaService::COLLECTION_IMAGES);
    }

    public function addImages(Pet $pet, array $images): Collection
    {
        return collect($images)
            ->map(fn (UploadedFile $image): Media => $this->addImage($pet, $image));
    }

    public function deleteImage(Pet $pet, Media $media): void
    {
        $media->delete();
    }

    public function getAttributesForAnimalType(AnimalType $animalType): Collection
    {
        return AttributeDefinition::whereHas('animalTypes', fn ($q) => $q->where('animal_types.id', $animalType->id))
            ->with('options')
            ->get();
    }

    public function resolveValueColumn(string $valueType): string
    {
        return match ($valueType) {
            'integer' => 'value_integer',
            'decimal' => 'value_decimal',
            'boolean' => 'value_boolean',
            'date' => 'value_date',
            default => 'value_text',
        };
    }

    public function validateAttributesForAnimalType(array $attributes, AnimalType $animalType): void
    {
        $definitions = AttributeDefinition::whereHas('animalTypes', fn ($q) => $q->where('animal_types.id', $animalType->id))
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($attributes as $index => $item) {
            $definitionId = $item['attribute_definition_id'];

            if (! $definitions->has($definitionId)) {
                $errors["attributes.$index.attribute_definition_id"] = [
                    'The attribute definition does not belong to this animal type.',
                ];

                continue;
            }

            $expectedColumn = $this->resolveValueColumn($definitions->get($definitionId)->value_type);
            $valueFields = ['value_text', 'value_integer', 'value_decimal', 'value_boolean', 'value_date'];
            $providedColumns = array_values(array_filter($valueFields, fn (string $f) => array_key_exists($f, $item) && $item[$f] !== null));

            if (count($providedColumns) === 1 && $providedColumns[0] !== $expectedColumn) {
                $errors["attributes.$index.{$providedColumns[0]}"] = [
                    'The provided value field does not match the attribute definition type.',
                ];
            }
        }

        throw_if(! empty($errors), ValidationException::withMessages($errors));
    }

    public function syncAttributes(Pet $pet, array $attributes): void
    {
        DB::transaction(function () use ($pet, $attributes): void {
            $allValueColumns = ['value_text', 'value_integer', 'value_decimal', 'value_boolean', 'value_date'];

            $definitionIds = array_column($attributes, 'attribute_definition_id');
            $definitions = AttributeDefinition::whereIn('id', $definitionIds)->get()->keyBy('id');

            foreach ($attributes as $item) {
                $definitionId = $item['attribute_definition_id'];
                $valueColumn = $this->resolveValueColumn($definitions->get($definitionId)->value_type);

                $values = array_fill_keys($allValueColumns, null);
                $values[$valueColumn] = Arr::get($item, $valueColumn);

                PetAttribute::updateOrCreate(
                    [
                        'pet_id' => $pet->id,
                        'attribute_definition_id' => $definitionId,
                    ],
                    [
                        'attribute_option_id' => $item['attribute_option_id'] ?? null,
                        ...$values,
                    ]
                );
            }
        });
    }
}
