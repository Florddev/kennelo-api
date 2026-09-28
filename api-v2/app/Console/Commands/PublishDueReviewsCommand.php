<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Review\ReviewPublicationService;
use Illuminate\Console\Command;

/**
 * Publie les avis restés seuls à la fin du délai pour en donner un.
 */
class PublishDueReviewsCommand extends Command
{
    protected $signature = 'reviews:publish-due';

    protected $description = 'Publish the reviews whose window to review closed';

    public function handle(ReviewPublicationService $publication): int
    {
        $published = $publication->publishDue();

        $this->info("Reviews published: {$published}.");

        return self::SUCCESS;
    }
}
