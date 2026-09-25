<?php

declare(strict_types=1);

namespace App\Http\Requests\Explore;

use App\Enums\LocationModeEnum;
use Illuminate\Validation\Rule;

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
        ];
    }
}
