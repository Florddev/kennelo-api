<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

abstract class ConcurrencyTestCase extends TestCase
{
    use DatabaseTruncation {
        truncateDatabaseTables as truncateTablesBeforeTest;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
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
