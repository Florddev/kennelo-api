<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Activity\ActivityDocumentService;
use Illuminate\Console\Command;

/**
 * Tâche quotidienne. L'ordre compte : un justificatif arrivé à échéance est d'abord marqué expiré,
 * puis les activités qui n'ont plus tous leurs justificatifs obligatoires sont suspendues.
 */
class ExpireActivityDocumentsCommand extends Command
{
    protected $signature = 'activities:expire-documents';

    protected $description = 'Expire overdue activity documents, warn before expiry and suspend activities missing a required document';

    public function handle(ActivityDocumentService $documents): int
    {
        $expired = $documents->expireOverdue();
        $warned = $documents->notifyExpiringSoon();
        $suspended = $documents->suspendActivitiesMissingDocuments();

        $this->info("Documents expired: {$expired}. Expiry warnings sent: {$warned}. Activities suspended: {$suspended}.");

        return self::SUCCESS;
    }
}
