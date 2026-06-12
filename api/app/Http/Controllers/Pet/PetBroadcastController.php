<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Events\PetBroadcast;
use App\Http\Controllers\Controller;
use App\Models\Scanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetBroadcastController extends Controller
{
    public function broadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'microchip_number' => ['required', 'string'],
            'scanner_code' => ['required', 'string', 'exists:scanners,code'],
        ]);

        $scanner = Scanner::where('code', $validated['scanner_code'])->firstOrFail();

        event(new PetBroadcast($validated['microchip_number'], $scanner->user_id, $scanner->code));

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
