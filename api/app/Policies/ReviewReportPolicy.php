<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ReviewReportPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
