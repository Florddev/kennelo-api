<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;

class PetByMicrochipController extends Controller
{
    public function show(string $microchipNumber): JsonResponse
    {
        $pet = Pet::where('microchip_number', $microchipNumber)
            ->with(['animalType', 'petAttributes.attributeDefinition', 'petAttributes.attributeOption', 'media'])
            ->firstOrFail();

        return (new PetResource($pet))
            ->additional(['status' => ApiStatusEnum::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }
}
