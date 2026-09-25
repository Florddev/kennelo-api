<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Activity\ActivityDocumentService;
use Illuminate\Console\Command;

/**
 * Tâche quotidienne : marque expirés les justificatifs échus et prévient l'équipe de ceux qui arrivent à échéance.
 */
class ExpireActivityDocumentsCommand extends Command
{
    protected $signature = 'activities:expire-documents';

    protected $description = 'Expire overdue activity documents and warn the team before expiry';

    public function handle(ActivityDocumentService $documents): int
    {
        $expired = $documents->expireOverdue();
        $warned = $documents->notifyExpiringSoon();

        $this->info("Documents expired: {$expired}. Expiry warnings sent: {$warned}.");

        return self::SUCCESS;
    }
}
