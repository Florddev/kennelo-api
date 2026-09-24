<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationLegalFormEnum: string
{
    case INDIVIDUAL = 'individual';
    case MICRO_ENTERPRISE = 'micro_enterprise';
    case COMPANY = 'company';
    case ASSOCIATION = 'association';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Un particulier exerce sans numéro SIREN ; toutes les autres formes en ont un.
     */
    public function hasSiren(): bool
    {
        return $this !== self::INDIVIDUAL;
    }
}
