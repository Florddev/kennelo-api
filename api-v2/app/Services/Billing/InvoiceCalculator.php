<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Support\Money;

/**
 * Totaux d'une facture. Les prix sont TTC : chaque ligne donne son TTC (prix × quantité, arrondi au centime), son
 * HT (TTC ÷ (1 + taux), arrondi) et sa TVA (la différence). La facture additionne ses lignes, sans arrondi de plus.
 * En franchise de TVA, le taux est nul : HT = TTC.
 */
final class InvoiceCalculator
{
    /**
     * @param  numeric-string  $unitPriceTtc
     * @param  numeric-string  $quantity
     * @param  numeric-string  $vatRate  en pourcentage (20.00)
     * @return array{total_ht: numeric-string, total_vat: numeric-string, total_ttc: numeric-string}
     */
    public static function line(string $unitPriceTtc, string $quantity, string $vatRate): array
    {
        $ttc = Money::multiply($unitPriceTtc, $quantity);
        $ht = Money::excludingVat($ttc, $vatRate);

        return ['total_ht' => $ht, 'total_vat' => bcsub($ttc, $ht, 2), 'total_ttc' => $ttc];
    }

    /**
     * @param  iterable<array{total_ht: numeric-string, total_vat: numeric-string, total_ttc: numeric-string}>  $lines
     * @return array{total_ht: numeric-string, total_vat: numeric-string, total_ttc: numeric-string}
     */
    public static function totals(iterable $lines): array
    {
        $totals = ['total_ht' => '0.00', 'total_vat' => '0.00', 'total_ttc' => '0.00'];

        foreach ($lines as $line) {
            foreach ($totals as $key => $total) {
                $totals[$key] = Money::sum($total, $line[$key]);
            }
        }

        return $totals;
    }
}
