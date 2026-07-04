<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalBreedResource;
use App\Models\AnimalBreed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Pets
 */
class AnimalBreedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'animal_type_id' => ['sometimes', 'uuid', 'exists:animal_types,id'],
        ]);

        $animalTypeId = $validated['animal_type_id'] ?? 'all';

        $breeds = Cache::rememberForever(
            "reference:animal_breeds:{$animalTypeId}",
            fn () => AnimalBreed::query()
                ->when(
                    $animalTypeId !== 'all',
                    fn ($query) => $query->where('animal_type_id', $animalTypeId)
                )
                ->get()
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
        );

        return AnimalBreedResource::collection($breeds)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
