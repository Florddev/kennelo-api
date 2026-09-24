<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles de la plateforme (Spatie). Les rôles au sein d'une entreprise sont dans organization_member_roles,
 * et l'accès à l'espace de gestion découle de l'appartenance à une entreprise (Gate « access-management »).
 * Crée les rôles manquants, ne modifie pas les rôles existants.
 */
class RoleSeeder extends Seeder
{
    private const ROLES = ['admin', 'user'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
        }
    }
}
