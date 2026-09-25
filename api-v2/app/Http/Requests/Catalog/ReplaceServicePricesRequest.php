<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use App\Models\AnimalBreed;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * La grille complète d'une prestation. Chaque ligne vise une espèce, précisée au choix par une race, par une
 * taille et un poil, ou par une taille seule ; deux lignes ne visent pas le même animal. Une prestation qui se
 * place dans l'agenda a une durée sur chaque ligne.
 */
class ReplaceServicePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return [
            'prices' => ['present', 'array', 'max:200'],
            'prices.*.animal_type_id' => ['required', 'uuid', Rule::exists('animal_types', 'id')],
            'prices.*.size_class' => ['nullable', Rule::enum(PetSizeClassEnum::class)],
            'prices.*.coat_type' => ['nullable', Rule::enum(PetCoatTypeEnum::class)],
            'prices.*.animal_breed_id' => ['nullable', 'uuid', Rule::exists('animal_breeds', 'id')],
            'prices.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'prices.*.duration_minutes' => [
                Rule::requiredIf($service->requires_scheduling),
                'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $lines = collect($this->input('prices'));
                $breedSpecies = AnimalBreed::query()
                    ->whereIn('id', $lines->pluck('animal_breed_id')->filter())
                    ->pluck('animal_type_id', 'id');

                foreach ($lines as $index => $line) {
                    $breedId = $line['animal_breed_id'] ?? null;

                    if ($breedId !== null && (isset($line['size_class']) || isset($line['coat_type']))) {
                        $validator->errors()->add("prices.{$index}.animal_breed_id", __('catalog.price_breed_exclusive'));
                    } elseif ($breedId !== null && $breedSpecies->get($breedId) !== $line['animal_type_id']) {
                        $validator->errors()->add("prices.{$index}.animal_breed_id", __('catalog.price_breed_mismatch'));
                    } elseif (isset($line['coat_type']) && ! isset($line['size_class'])) {
                        $validator->errors()->add("prices.{$index}.coat_type", __('catalog.price_coat_requires_size'));
                    }
                }

                $targets = $lines->map(fn (array $line): string => implode('|', [
                    $line['animal_type_id'],
                    $line['size_class'] ?? '',
                    $line['coat_type'] ?? '',
                    $line['animal_breed_id'] ?? '',
                ]));

                if ($targets->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('prices', __('catalog.duplicate_price'));
                }
            },
        ];
    }
}
