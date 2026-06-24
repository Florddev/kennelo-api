<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Services\Scanner\ScannerScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetBroadcastController extends Controller
{
    public function __construct(
        private ScannerScanService $scannerScanService
    ) {}

    public function broadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'microchip_number' => ['required', 'string'],
            'scanner_code' => ['required', 'string', 'exists:scanners,code'],
        ]);

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
