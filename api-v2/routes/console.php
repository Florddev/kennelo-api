<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Justificatifs des activités : expiration et avertissement avant échéance.
Schedule::command('activities:expire-documents')
    ->dailyAt('03:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onOneServer();

// Réservations : demandes sans réponse, rappels à l'équipe, début et fin des séjours et des rendez-vous,
// versements aux entreprises.
Schedule::command('bookings:expire')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('bookings:remind')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('bookings:advance')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('bookings:release-payouts')
    ->dailyAt('06:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onOneServer();
