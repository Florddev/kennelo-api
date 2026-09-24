<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

/**
 * Crée une personne membre active de l'entreprise, avec un rôle facultatif.
 */
function memberOf(Organization $organization, ?OrganizationRoleEnum $role = null, ?string $activityId = null): User
{
    $factory = OrganizationMember::factory()->for($organization);

    if ($role !== null) {
        $factory = $factory->withRole($role, $activityId);
    }

    return $factory->create()->user;
}

/**
 * Insère une activité minimale et retourne son identifiant.
 * À remplacer par la factory Activity quand le modèle arrivera (lot « Métiers, activités et catalogue »).
 */
function activityFor(Organization $organization): string
{
    $categoryId = (string) Str::uuid();
    $professionId = (string) Str::uuid();
    $activityId = (string) Str::uuid();

    DB::table('profession_categories')->insert(['id' => $categoryId, 'code' => 'category_'.$categoryId, 'name' => '{"fr":"Soin"}']);
    DB::table('professions')->insert([
        'id' => $professionId,
        'profession_category_id' => $categoryId,
        'code' => 'profession_'.$professionId,
        'name' => '{"fr":"Toilettage"}',
        'booking_mode' => 'appointment',
        'billing_unit' => 'slot',
    ]);
    DB::table('activities')->insert([
        'id' => $activityId,
        'organization_id' => $organization->id,
        'profession_id' => $professionId,
        'name' => 'Salon des Lilas',
    ]);

    return $activityId;
}
