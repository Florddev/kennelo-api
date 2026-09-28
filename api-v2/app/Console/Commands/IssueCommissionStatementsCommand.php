<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\CommissionStatementService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Émet le récapitulatif de commission du mois écoulé à chaque entreprise versée ce mois-là. Relancée, elle
 * n'émet que les récapitulatifs qui manquent.
 */
class IssueCommissionStatementsCommand extends Command
{
    protected $signature = 'billing:issue-commission-statements {--month= : Month to invoice (YYYY-MM), the previous one by default}';

    protected $description = 'Invoice each organization the commissions of a month';

    public function handle(CommissionStatementService $statements): int
    {
        $timezone = (string) config('billing.timezone');
        $option = $this->option('month');

        try {
            $month = is_string($option)
                ? CarbonImmutable::createFromFormat('!Y-m', $option, $timezone)
                : CarbonImmutable::now($timezone)->startOfMonth()->subMonth();
        } catch (InvalidArgumentException) {
            $month = false;
        }

        if (! $month instanceof CarbonImmutable) {
            $this->error('The month is expected as YYYY-MM.');

            return self::INVALID;
        }

        $result = $statements->issueForMonth($month);

        $this->info("Commission statements for {$month->format('Y-m')}: {$result['issued']} issued, {$result['failed']} failed.");

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
