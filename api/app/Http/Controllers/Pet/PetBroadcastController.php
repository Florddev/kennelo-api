<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Events\PetBroadcast;
use App\Http\Controllers\Controller;
use App\Models\Scanner;
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

        $dedupeKey = "pet-broadcast:{$validated['scanner_code']}:{$validated['microchip_number']}";

        if (Cache::add($dedupeKey, true, self::DEDUPE_WINDOW_SECONDS)) {
            $scanner = Scanner::where('code', $validated['scanner_code'])->firstOrFail();

            event(new PetBroadcast($validated['microchip_number'], $scanner->user_id, $scanner->code));
        }

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
