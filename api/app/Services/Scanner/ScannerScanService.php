<?php

declare(strict_types=1);

namespace App\Services\Scanner;

use App\Events\PetBroadcast;
use App\Models\Pet;
use App\Models\Scanner;
use App\Models\ScannerScan;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ScannerScanService
{
    private const DEDUPE_WINDOW_SECONDS = 5;

    public function getUserScans(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 30;

        return ScannerScan::whereIn('scanner_id', $user->scanners()->select('id'))
            ->with(['scanner', 'pet.animalType', 'pet.media'])
            ->orderByDesc('scanned_at')
            ->paginate($perPage);
    }

    public function recordAndBroadcast(string $microchipNumber, string $scannerCode): ?Pet
    {
        $scanner = Scanner::where('code', $scannerCode)->firstOrFail();
        $pet = Pet::with('animalType')->where('microchip_number', $microchipNumber)->first();

        $dedupeKey = "pet-broadcast:{$scannerCode}:{$microchipNumber}";

        if (Cache::add($dedupeKey, true, self::DEDUPE_WINDOW_SECONDS)) {
            ScannerScan::create([
                'scanner_id' => $scanner->id,
                'pet_id' => $pet?->id,
                'microchip_number' => $microchipNumber,
                'found' => $pet !== null,
                'scanned_at' => now(),
            ]);

            event(new PetBroadcast($microchipNumber, $scanner->user_id, $scanner->code, $pet));
        }

        return $pet;
    }
}
