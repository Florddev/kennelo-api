<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Droits dans une entreprise. Le propriétaire les a tous ; les autres membres les tiennent de leurs rôles.
 * Fermer ou céder l'entreprise n'est pas un droit : c'est réservé au propriétaire.
 */
enum OrganizationPermissionEnum: string
{
    case ORGANIZATION_MANAGE = 'organization.manage';
    case TEAM_MANAGE = 'team.manage';
    case BILLING_MANAGE = 'billing.manage';
    case CATALOG_MANAGE = 'catalog.manage';
    case ACTIVITY_MANAGE = 'activity.manage';
    case BOOKINGS_MANAGE = 'bookings.manage';
    case BOOKINGS_VIEW = 'bookings.view';
    case AGENDA_MANAGE = 'agenda.manage';
    case MESSAGES_REPLY = 'messages.reply';
    case FINANCE_VIEW = 'finance.view';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
