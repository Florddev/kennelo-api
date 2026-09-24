<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\Reference\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeStripe;

abstract class TestCase extends BaseTestCase
{
    private FakeStripe $fakeStripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeStripe = FakeStripe::install();

        $this->seed(RoleSeeder::class);

        // Chaque requête arrive « du front », comme en production : Sanctum ouvre alors une session (mode SPA).
        $this->withHeader('Referer', (string) config('app.url'));
    }

    /**
     * Toute requête Stripe passe par ce faux client ; un test déclare les réponses qu'il attend.
     */
    protected function stripe(): FakeStripe
    {
        return $this->fakeStripe;
    }
}
