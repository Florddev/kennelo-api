<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

/**
 * Tests de demandes simultanées : une seconde connexion y joue la demande concurrente, et ne voit que des données
 * validées. Ces tests ne tournent donc pas dans une transaction annulée à la fin (RefreshDatabase) : les tables
 * sont vidées avant chacun d'eux (DatabaseTruncation) et après, pour les tests suivants du même processus.
 *
 * PostgreSQL uniquement : SQLite verrouille toute la base et non des lignes, et sa base en mémoire n'est pas
 * partagée entre connexions. Le test est ignoré avant l'amorçage de la base, qui échouerait sous SQLite.
 */
abstract class ConcurrencyTestCase extends TestCase
{
    use DatabaseTruncation {
        truncateDatabaseTables as truncateTablesBeforeTest;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
            // Un test interrompu en pleine transaction la laisse ouverte : elle annulerait le nettoyage.
            DB::rollBack(0);

            $this->truncateTablesForAllConnections();
        });
    }

    protected function truncateDatabaseTables(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL only.');
        }

        $this->truncateTablesBeforeTest();
    }
}
