<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ActivityDocument;
use App\Models\User;

/**
 * Revue des justificatifs par Kennelo. L'équipe de l'activité y accède au travers de ActivityPolicy::update.
 */
class ActivityDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function review(User $user, ActivityDocument $document): bool
    {
        return $user->hasRole('admin');
    }
}
