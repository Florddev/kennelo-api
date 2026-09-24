<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\Reference\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Chaque requête arrive « du front », comme en production : Sanctum ouvre alors une session (mode SPA).
        $this->withHeader('Referer', (string) config('app.url'));
    }
}
