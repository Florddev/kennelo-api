<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\BroadcastPetRequest;
use App\Services\Scanner\ScannerScanService;
use Illuminate\Http\JsonResponse;

class PetBroadcastController extends Controller
{
    public function __construct(
        private ScannerScanService $scannerScanService
    ) {}

    public function broadcast(BroadcastPetRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pet = $this->scannerScanService->recordAndBroadcast(
            $validated['microchip_number'],
            $validated['scanner_code'],
        );

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
            'found' => $pet !== null,
            'name' => $pet?->name,
            'species' => $pet?->animalType?->name,
            'breed' => $pet?->breed,
        ]);
    }
}
