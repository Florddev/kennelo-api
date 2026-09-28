<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Numéros de facture continus, sans trou : un compteur par émetteur et par année (Kennelo est l'émetteur NULL),
 * partagé par les factures et les avoirs.
 *
 * Le compteur est verrouillé (FOR UPDATE) jusqu'à la fin de la transaction d'émission : deux émissions simultanées
 * se suivent, et une émission qui échoue rend son numéro en annulant la transaction.
 */
class InvoiceNumberGenerator
{
    public function next(?string $issuerOrganizationId, int $year): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('An invoice number is taken inside the transaction that issues the invoice.');
        }

        $sequence = $this->lockedSequence($issuerOrganizationId, $year);
        $sequence->last_number++;
        $sequence->save();

        $prefix = config($issuerOrganizationId === null ? 'billing.number_prefixes.kennelo' : 'billing.number_prefixes.organization');

        return sprintf('%s-%d-%06d', $prefix, $year, $sequence->last_number);
    }

    private function lockedSequence(?string $issuerOrganizationId, int $year): InvoiceSequence
    {
        $query = fn () => InvoiceSequence::query()
            ->where('issuer_organization_id', $issuerOrganizationId)
            ->where('year', $year)
            ->lockForUpdate();

        $sequence = $query()->first();

        if ($sequence !== null) {
            return $sequence;
        }

        // Première facture de l'année. Sous PostgreSQL, l'index unique NULLS NOT DISTINCT fait d'une création
        // simultanée un no-op ; SQLite, lui, n'exécute qu'une transaction d'écriture à la fois.
        InvoiceSequence::query()->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'issuer_organization_id' => $issuerOrganizationId,
            'year' => $year,
            'last_number' => 0,
        ]);

        return $query()->firstOrFail();
    }
}
