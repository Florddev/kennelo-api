<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\PayoutStatusEnum;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Organization;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Récapitulatif mensuel de commission : la facture de Kennelo à l'entreprise pour les commissions du mois.
 *
 * Une commission est facturée le mois où la réservation est versée à l'entreprise : elle ne change plus ensuite
 * (la réservation est terminée, ou annulée en gardant une part pour le pro). Une ligne par réservation versée, au
 * taux de TVA de Kennelo. Les mois se comptent dans le fuseau de la facturation. Un seul récapitulatif par
 * entreprise et par mois ; rien n'est émis pour un mois sans versement.
 */
class CommissionStatementService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * Émet le récapitulatif du mois de chaque entreprise versée ce mois-là, fermée depuis ou non. Une entreprise
     * en échec n'empêche pas les autres : l'erreur est signalée et la commande peut être relancée.
     *
     * @return array{issued: int, failed: int}
     */
    public function issueForMonth(CarbonImmutable $month): array
    {
        $result = ['issued' => 0, 'failed' => 0];

        Organization::withTrashed()
            ->whereHas('bookings', fn (Builder $bookings) => $this->commissioned($bookings, $month))
            ->lazyById()
            ->each(function (Organization $organization) use ($month, &$result): void {
                try {
                    $result['issued'] += (int) ($this->issue($organization, $month) !== null);
                } catch (Throwable $exception) {
                    report($exception);
                    $result['failed']++;
                }
            });

        return $result;
    }

    public function issue(Organization $organization, CarbonImmutable $month): ?Invoice
    {
        [$start, $end] = $this->period($month);

        return DB::transaction(function () use ($organization, $month, $start, $end): ?Invoice {
            $alreadyIssued = Invoice::query()
                ->where('recipient_organization_id', $organization->id)
                ->whereDate('period_start', $start->toDateString())
                ->exists();

            if ($alreadyIssued) {
                return null;
            }

            $bookings = $this->commissioned($organization->bookings()->getQuery(), $month)
                ->with(['activity', 'payout'])
                ->get()
                ->sortBy(fn (Booking $booking): string => $booking->payout?->transferred_at?->toISOString().$booking->id);

            if ($bookings->isEmpty()) {
                return null;
            }

            $vatRate = Money::round((string) config('billing.kennelo.vat_rate'));
            $lines = $bookings->map(fn (Booking $booking): array => [
                'description' => __('billing.lines.commission', [
                    'reference' => mb_strtoupper(mb_substr($booking->id, 0, 8)),
                    'activity' => $booking->activity->name ?? '',
                    'start' => $booking->start_date->locale((string) config('billing.locale'))->isoFormat('L'),
                    'end' => $booking->end_date->locale((string) config('billing.locale'))->isoFormat('L'),
                ], (string) config('billing.locale')),
                'quantity' => '1',
                'unit_price_ttc' => $booking->platform_fee,
                'vat_rate' => $vatRate,
            ])->values()->all();

            return $this->invoices->issue(null, $organization->loadMissing('address'), $lines, [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ]);
        });
    }

    /**
     * Réservations dont la commission est acquise ce mois-là : versées à l'entreprise pendant le mois, sans que
     * le versement ait échoué ou ait été annulé.
     *
     * @param  Builder<Booking>  $bookings
     * @return Builder<Booking>
     */
    private function commissioned(Builder $bookings, CarbonImmutable $month): Builder
    {
        [$start, $end] = $this->period($month);

        return $bookings
            ->where('platform_fee', '>', 0)
            ->whereHas('payout', fn (Builder $payout) => $payout
                ->whereNotIn('status', [PayoutStatusEnum::FAILED, PayoutStatusEnum::CANCELED])
                ->whereBetween('transferred_at', [$start->utc(), $end->utc()]));
    }

    /**
     * Premier et dernier instant du mois de $month (son année et son mois), dans le fuseau de la facturation.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function period(CarbonImmutable $month): array
    {
        $start = CarbonImmutable::create($month->year, $month->month, 1, 0, 0, 0, (string) config('billing.timezone'));

        return [$start, $start->endOfMonth()];
    }
}
