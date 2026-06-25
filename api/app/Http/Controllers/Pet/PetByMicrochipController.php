<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PetResource;
use App\Services\Pet\PetService;
use Illuminate\Http\JsonResponse;

class PetByMicrochipController extends Controller
{
    public function __construct(
        private PetService $petService
    ) {}

    public function show(string $microchipNumber): JsonResponse
    {
        $pet = $this->petService->findByMicrochip($microchipNumber);

        return (new PetResource($pet))
            ->additional(['status' => ApiStatusEnum::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }
}
