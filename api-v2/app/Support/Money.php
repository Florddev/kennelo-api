<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Calculs sur des montants en euros, exprimés en chaînes décimales (« 12.50 ») comme les colonnes decimal(10,2).
 *
 * Tout passe par bcmath pour éviter les erreurs d'arrondi des flottants. Chaque résultat est arrondi au centime,
 * au plus proche, la moitié s'éloignant de zéro (12,345 → 12,35). Les centimes n'apparaissent qu'à l'appel à Stripe.
 */
final class Money
{
    private const int SCALE = 2;

    /** Précision des calculs intermédiaires, avant l'arrondi au centime. */
    private const int WORKING_SCALE = 10;

    /**
     * @param  numeric-string  $amount
     * @return numeric-string
     */
    public static function round(string $amount): string
    {
        // bcmath tronque : on ajoute un demi-centime du signe du montant avant de couper au centime.
        $halfCent = str_starts_with($amount, '-') ? '-0.005' : '0.005';
        $rounded = bcadd(bcadd($amount, $halfCent, self::WORKING_SCALE), '0', self::SCALE);

        // bcmath peut écrire « -0.00 ».
        return bccomp($rounded, '0', self::SCALE) === 0 ? '0.00' : $rounded;
    }

    /**
     * @param  numeric-string  ...$amounts
     * @return numeric-string
     */
    public static function sum(string ...$amounts): string
    {
        $total = array_reduce($amounts, fn (string $total, string $amount): string => bcadd($total, $amount, self::WORKING_SCALE), '0');

        return self::round($total);
    }

    /**
     * @param  numeric-string  $amount
     * @param  numeric-string  $factor
     * @return numeric-string
     */
    public static function multiply(string $amount, string $factor): string
    {
        return self::round(bcmul($amount, $factor, self::WORKING_SCALE));
    }

    /**
     * Applique une variation en pourcentage : +15 majore de 15 %, -20 remise de 20 %.
     *
     * @param  numeric-string  $amount
     * @param  numeric-string  $percent
     * @return numeric-string
     */
    public static function adjust(string $amount, string $percent): string
    {
        $factor = bcdiv(bcadd('100', $percent, self::WORKING_SCALE), '100', self::WORKING_SCALE);

        return self::multiply($amount, $factor);
    }

    /**
     * Part de $amount au prorata de $part sur $whole : les frais d'une option retirée d'une réservation, par exemple.
     *
     * @param  numeric-string  $amount
     * @param  numeric-string  $part
     * @param  numeric-string  $whole
     * @return numeric-string
     */
    public static function prorate(string $amount, string $part, string $whole): string
    {
        if (bccomp($whole, '0', self::WORKING_SCALE) === 0) {
            return '0.00';
        }

        return self::round(bcdiv(bcmul($amount, $part, self::WORKING_SCALE), $whole, self::WORKING_SCALE));
    }

    /**
     * Montant hors taxes d'un montant TTC : 12,00 € à 20 % donnent 10,00 €. La TVA est la différence, ce qui garde
     * HT + TVA = TTC au centime près.
     *
     * @param  numeric-string  $amountIncludingVat
     * @param  numeric-string  $vatRate  en pourcentage (20.00)
     * @return numeric-string
     */
    public static function excludingVat(string $amountIncludingVat, string $vatRate): string
    {
        $divisor = bcadd('1', bcdiv($vatRate, '100', self::WORKING_SCALE), self::WORKING_SCALE);

        return self::round(bcdiv($amountIncludingVat, $divisor, self::WORKING_SCALE));
    }

    /**
     * @param  numeric-string  $first
     * @param  numeric-string  $second
     * @return numeric-string
     */
    public static function min(string $first, string $second): string
    {
        return bccomp($first, $second, self::WORKING_SCALE) <= 0 ? $first : $second;
    }

    /**
     * @param  numeric-string  $amount
     */
    public static function toCents(string $amount): int
    {
        return (int) bcmul(self::round($amount), '100', 0);
    }

    /**
     * @return numeric-string
     */
    public static function fromCents(int $cents): string
    {
        return bcdiv((string) $cents, '100', self::SCALE);
    }

    /**
     * Montant écrit à la française (« 1 234,50 », espace insécable), sans passer par un flottant ni par intl.
     *
     * @param  numeric-string  $amount
     */
    public static function format(string $amount): string
    {
        $rounded = self::round($amount);
        [$units, $cents] = explode('.', ltrim($rounded, '-'));
        // Groupes de trois chiffres comptés depuis la droite : 1234567 → 1, 234, 567.
        $groups = array_reverse(array_map(strrev(...), str_split(strrev($units), 3)));

        return (str_starts_with($rounded, '-') ? '-' : '').implode("\u{A0}", $groups).','.$cents;
    }
}
