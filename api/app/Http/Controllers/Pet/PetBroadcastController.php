<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Events\PetBroadcast;
use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\Scanner;
use App\Models\ScannerScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PetBroadcastController extends Controller
{
    private const DEDUPE_WINDOW_SECONDS = 5;

    public function broadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'microchip_number' => ['required', 'string'],
            'scanner_code' => ['required', 'string', 'exists:scanners,code'],
        ]);

        $scanner = Scanner::where('code', $validated['scanner_code'])->firstOrFail();
        $pet = Pet::with('animalType')->where('microchip_number', $validated['microchip_number'])->first();

        $dedupeKey = "pet-broadcast:{$validated['scanner_code']}:{$validated['microchip_number']}";

        if (Cache::add($dedupeKey, true, self::DEDUPE_WINDOW_SECONDS)) {
            ScannerScan::create([
                'scanner_id' => $scanner->id,
                'pet_id' => $pet?->id,
                'microchip_number' => $validated['microchip_number'],
                'found' => $pet !== null,
                'scanned_at' => now(),
            ]);

            event(new PetBroadcast($validated['microchip_number'], $scanner->user_id, $scanner->code, $pet));
        }

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
