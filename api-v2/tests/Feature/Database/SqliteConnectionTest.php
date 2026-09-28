<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('opens a SQLite database only when it is queried', function () {
    config(['database.connections.missing' => [...config('database.connections.sqlite'), 'database' => storage_path('framework/testing/missing.sqlite')]]);

    // Le démarrage de l'application crée des connexions (cache des permissions) sans rien leur demander : il ne
    // doit pas exiger de base (composer install, config:cache).
    $connection = DB::connection('missing');

    expect(fn () => $connection->select('select 1'))->toThrow(QueryException::class, 'does not exist');
});
