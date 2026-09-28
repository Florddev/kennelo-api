<?php

declare(strict_types=1);

namespace App\Http\Requests\Explore;

use App\Enums\LocationModeEnum;
use App\Models\AnimalType;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Filtres de la recherche. Avec une position, le rayon ne vaut que pour les activités qui reçoivent chez elles ;
 * une activité qui se déplace doit couvrir la position du client avec son propre rayon.
 */
class SearchActivitiesRequest extends ExploreRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'profession' => ['sometimes', 'string', Rule::exists('professions', 'code')->where('is_active', true)],
            'category' => ['sometimes', 'string', Rule::exists('profession_categories', 'code')],
            'animal_type' => ['sometimes', 'string', Rule::exists('animal_types', 'code')],
            'location_mode' => ['sometimes', Rule::enum(LocationModeEnum::class)],
            'radius' => ['sometimes', 'integer', 'min:1', 'max:'.config('activities.max_radius_km')],
            'location' => ['sometimes', 'string', 'max:100'],
            'host_type' => ['sometimes', Rule::in(['pro', 'individual'])],
            'sort' => ['sometimes', Rule::in(['distance', 'newest'])],
            'start_date' => ['required_with:end_date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'animals' => ['sometimes', 'array', 'max:10'],
            'animals.*' => ['integer', 'min:1', 'max:20'],
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

                if ($this->filled('end_date') && CarbonImmutable::parse($this->string('start_date')->toString())->diffInDays($this->string('end_date')->toString()) > (int) config('booking.max_stay_days')) {
                    $validator->errors()->add('end_date', __('booking.invalid_length', ['max' => config('booking.max_stay_days')]));
                }

                $codes = array_keys((array) $this->input('animals', []));

                if ($codes !== [] && AnimalType::query()->whereIn('code', $codes)->count() !== count($codes)) {
                    $validator->errors()->add('animals', __('validation.exists', ['attribute' => 'animals']));
                }
            },
        ];
    }
}
