<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Justificatifs qu'un métier peut exiger (profession_document_requirements).
 */
enum DocumentTypeEnum: string
{
    case RC_PRO_INSURANCE = 'rc_pro_insurance';
    case ACACED = 'acaced';
    case PREFECTURE_DECLARATION = 'prefecture_declaration';
    case OSTEOPATH_REGISTRATION = 'osteopath_registration';
    case ANIMAL_TRANSPORT_CERTIFICATE = 'animal_transport_certificate';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
