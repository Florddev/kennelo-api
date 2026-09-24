<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Authentifie les requêtes suivantes du test en tant que $user (session Sanctum).
 * Retourne un tableau d'en-têtes vide pour s'utiliser en dernier argument des appels HTTP.
 *
 * @return array<string, string>
 */
function asUser(User $user): array
{
    // Rechargé depuis la base comme lors d'une vraie requête (valeurs par défaut incluses : locale, status…).
    test()->actingAs($user->refresh());

    return [];
}

function adminUser(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}
