<?php

declare(strict_types=1);

namespace App\Services\Scanner;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ScannerService
{
    /** @return Collection<int, Scanner> */
    public function getUserScanners(User $user): Collection
    {
        return Scanner::where('user_id', $user->id)->orderByDesc('created_at')->get();
    }

    public function create(User $user, array $data): Scanner
    {
        return Scanner::create([
            'user_id' => $user->id,
            'code' => $data['code'],
            'name' => $data['name'] ?? null,
        ]);
    }

    public function update(Scanner $scanner, array $data): Scanner
    {
        $scanner->update($data);

        return $scanner;
    }

    public function delete(Scanner $scanner): void
    {
        $scanner->delete();
    }
}
