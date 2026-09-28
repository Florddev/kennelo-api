<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Une facture ne se modifie jamais : un remboursement produit un avoir, qui annule tout ou partie de la facture
 * qu'il vise. Factures et avoirs d'un émetteur se partagent la même numérotation.
 */
enum InvoiceTypeEnum: string
{
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
