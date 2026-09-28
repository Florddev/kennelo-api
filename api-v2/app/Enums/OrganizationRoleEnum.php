<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\OrganizationPermissionEnum as Permission;

enum OrganizationRoleEnum: string
{
    case MANAGER = 'manager';
    case ACTIVITY_MANAGER = 'activity_manager';
    case EMPLOYEE = 'employee';
    case ACCOUNTANT = 'accountant';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Rôles qui portent ce droit.
     *
     * @return list<self>
     */
    public static function granting(Permission $permission): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => in_array($permission, $role->permissions(), true),
        ));
    }

    /**
     * Un rôle lié à une activité ne s'exerce que sur celle-ci ; les autres portent sur toute l'entreprise.
     */
    public function isActivityScoped(): bool
    {
        return in_array($this, [self::ACTIVITY_MANAGER, self::EMPLOYEE], true);
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::MANAGER => [
                Permission::ORGANIZATION_MANAGE,
                Permission::TEAM_MANAGE,
                Permission::BILLING_MANAGE,
                Permission::CATALOG_MANAGE,
                Permission::ACTIVITY_MANAGE,
                Permission::BOOKINGS_MANAGE,
                Permission::BOOKINGS_VIEW,
                Permission::AGENDA_MANAGE,
                Permission::MESSAGES_REPLY,
                Permission::FINANCE_VIEW,
            ],
            self::ACTIVITY_MANAGER => [
                Permission::ACTIVITY_MANAGE,
                Permission::BOOKINGS_MANAGE,
                Permission::BOOKINGS_VIEW,
                Permission::AGENDA_MANAGE,
                Permission::MESSAGES_REPLY,
            ],
            // Un employé déclare aussi ses propres absences : la règle porte sur sa ressource, pas sur un droit.
            self::EMPLOYEE => [
                Permission::BOOKINGS_VIEW,
                Permission::MESSAGES_REPLY,
            ],
            self::ACCOUNTANT => [
                Permission::FINANCE_VIEW,
            ],
        };
    }
}
