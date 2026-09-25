<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Justificatifs des activités : expiration, avertissement avant échéance, suspension si un justificatif obligatoire manque.
Schedule::command('activities:expire-documents')
    ->dailyAt('03:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onOneServer();
