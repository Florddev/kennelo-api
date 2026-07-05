<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Admin\Prospect\ProspectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProspectsFromApifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public string $importId) {}

    public function handle(ProspectService $service): void
    {
        $service->processImport($this->importId);
    }
}
