<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalTypeResource;
use App\Models\AnimalType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Pets
 */
class AnimalTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $animalTypes = Cache::rememberForever(
            'reference:animal_types',
            fn () => AnimalType::with(['attributeDefinitions.options'])->get()
        );

        return AnimalTypeResource::collection($animalTypes)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
