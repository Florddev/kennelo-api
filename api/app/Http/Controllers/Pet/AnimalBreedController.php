<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalBreedResource;
use App\Models\AnimalBreed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $breeds = AnimalBreed::query()
            ->when(
                isset($validated['animal_type_id']),
                fn ($query) => $query->where('animal_type_id', $validated['animal_type_id'])
            )
            ->orderBy('breed')
            ->get();

        return AnimalBreedResource::collection($breeds)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
