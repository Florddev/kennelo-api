<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalBreedResource;
use App\Models\AnimalBreed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Pets
 */
class AnimalBreedController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'animal_type_id' => ['sometimes', 'uuid', 'exists:animal_types,id'],
        ]);

        // Tri sur le libellé traduit dans la langue de la requête (le libellé est stocké en json).
        $breeds = AnimalBreed::query()
            ->when(
                isset($validated['animal_type_id']),
                fn ($query) => $query->where('animal_type_id', $validated['animal_type_id'])
            )
            ->get()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return AnimalBreedResource::collection($breeds);
    }
}
