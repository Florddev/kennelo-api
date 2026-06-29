<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Scanner;
use App\Models\User;

class ScannerPolicy
{
    public function update(User $user, Scanner $scanner): bool
    {
        return (string) $scanner->user_id === (string) $user->id;
    }

    public function delete(User $user, Scanner $scanner): bool
    {
        return (string) $scanner->user_id === (string) $user->id;
    }
}
