<?php

declare(strict_types=1);

namespace App\Services\Admin\Stats;

use App\Enums\ActivityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\OrganizationStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingRefund;
use App\Models\BookingUnit;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Services\Review\ReviewService;
use App\Support\Money;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Indicateurs du back-office, gardés deux minutes en cache. Les séries mensuelles couvrent les six derniers mois,
 * le mois en cours compris, comptés en UTC. Les montants sont les montants nets actuels des réservations : ils
 * suivent les compléments et les remboursements.
 */
class StatsService
{
    private const int CACHE_SECONDS = 120;

    private const int MONTHS = 6;

    public function __construct(private readonly ReviewService $reviews) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return Cache::remember('admin:stats:overview', self::CACHE_SECONDS, fn (): array => [
            'organizations' => [
                'total' => Organization::query()->count(),
                'by_status' => $this->countBy(Organization::query(), 'status', OrganizationStatusEnum::cases()),
            ],
            'activities' => [
                'total' => Activity::query()->count(),
                'by_status' => $this->countBy(Activity::query(), 'status', ActivityStatusEnum::cases()),
                'bookable' => Activity::query()->bookable()->count(),
            ],
            'users' => [
                'total' => User::withInactive()->count(),
                'last_30_days' => User::withInactive()->where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'bookings' => [
                'total' => Booking::query()->count(),
                'pending' => Booking::query()->where('status', BookingStatusEnum::PENDING)->count(),
            ],
            'pending_review_reports' => ReviewReport::query()->where('status', ReviewReportStatusEnum::PENDING)->count(),
        ]);
    }

    /**
     * Volume d'affaires des réservations encaissées : ce que paient les clients (gmv), ce qu'en garde Kennelo
     * (frais de service et commission), ce qui revient aux entreprises, et les remboursements.
     *
     * @return array<string, mixed>
     */
    public function finance(): array
    {
        return Cache::remember('admin:stats:finance', self::CACHE_SECONDS, function (): array {
            $paid = fn (): Builder => Booking::query()->whereIn('payment_status', [PaymentStatusEnum::SUCCEEDED, PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::REFUNDED]);
            $totals = $paid()->toBase()
                ->selectRaw('count(*) as bookings, sum(total_price) as gmv, sum(service_fee) as service_fees, sum(platform_fee) as commissions, sum(activity_amount) as net_to_pros')
                ->first();

            $gmv = Money::round((string) ($totals->gmv ?? 0));
            $revenue = Money::sum((string) ($totals->service_fees ?? 0), (string) ($totals->commissions ?? 0));
            $count = (int) ($totals->bookings ?? 0);

            return [
                'paid_bookings' => $count,
                'gmv' => $gmv,
                'kennelo_revenue' => $revenue,
                'take_rate' => $this->percentage((float) $revenue, (float) $gmv),
                'net_to_pros' => Money::round((string) ($totals->net_to_pros ?? 0)),
                'refunds' => Money::round((string) BookingRefund::query()->sum('amount')),
                'average_basket' => $count > 0 ? Money::round(bcdiv($gmv, (string) $count, 4)) : '0.00',
                'gmv_by_month' => $this->withVariation($this->monthly($paid(), 'total_price')),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function bookings(): array
    {
        return Cache::remember('admin:stats:bookings', self::CACHE_SECONDS, function (): array {
            $byStatus = $this->countBy(Booking::query(), 'status', BookingStatusEnum::cases());
            $total = array_sum($byStatus);
            $lost = $byStatus[BookingStatusEnum::CANCELLED->value] + $byStatus[BookingStatusEnum::REJECTED->value] + $byStatus[BookingStatusEnum::EXPIRED->value];
            $nights = BookingUnit::query()->avg('nights');

            return [
                'total' => $total,
                'by_status' => $byStatus,
                'cancellation_rate' => $this->percentage($lost, $total),
                'payment_rate' => $this->percentage(Booking::query()->whereIn('payment_status', [PaymentStatusEnum::SUCCEEDED, PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::REFUNDED])->count(), $total),
                'average_stay_nights' => $nights === null ? null : round((float) $nights, 1),
                'by_month' => $this->withVariation($this->monthly(Booking::query())),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function community(): array
    {
        return Cache::remember('admin:stats:community', self::CACHE_SECONDS, function (): array {
            $users = User::withInactive()->toBase()
                ->selectRaw('count(*) as total')
                ->selectRaw('sum(case when email_verified_at is not null then 1 else 0 end) as verified')
                ->selectRaw('sum(case when last_seen_at >= ? then 1 else 0 end) as daily', [now()->subDay()])
                ->selectRaw('sum(case when last_seen_at >= ? then 1 else 0 end) as weekly', [now()->subWeek()])
                ->selectRaw('sum(case when last_seen_at >= ? then 1 else 0 end) as monthly', [now()->subMonth()])
                ->first();
            $published = Review::query()->byClients()->published();

            return [
                'users_by_month' => $this->withVariation($this->monthly(User::withInactive())),
                'email_verified_rate' => $this->percentage((int) ($users->verified ?? 0), (int) ($users->total ?? 0)),
                'active_users' => [
                    'daily' => (int) ($users->daily ?? 0),
                    'weekly' => (int) ($users->weekly ?? 0),
                    'monthly' => (int) ($users->monthly ?? 0),
                ],
                'professionals' => User::query()->where(fn (Builder $query) => $query
                    ->whereHas('ownedOrganizations')
                    ->orWhereHas('organizationMemberships', fn (Builder $member) => $member->active()))
                    ->count(),
                'reviews' => [
                    ...$this->reviews->rating($published),
                    'response_rate' => $this->percentage((clone $published)->whereHas('response')->count(), (clone $published)->count()),
                ],
                'messages_last_30_days' => Message::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'active_conversations' => Conversation::query()->where('last_message_at', '>=', now()->subDays(30))->count(),
            ];
        });
    }

    /**
     * Nombre de lignes par valeur de la colonne, chaque valeur de l'énumération présente.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<BackedEnum>  $cases
     * @return array<string, int>
     */
    private function countBy(Builder $query, string $column, array $cases): array
    {
        $counts = $query->toBase()->selectRaw("{$column} as value, count(*) as total")->groupBy($column)->pluck('total', 'value');

        return collect($cases)->mapWithKeys(fn (BackedEnum $case): array => [(string) $case->value => (int) ($counts[$case->value] ?? 0)])->all();
    }

    /**
     * Nombre de lignes, ou somme d'une colonne, par mois de création.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, float>
     */
    private function monthly(Builder $query, ?string $sum = null): array
    {
        $month = DB::connection()->getDriverName() === 'pgsql' ? "to_char(created_at, 'YYYY-MM')" : "strftime('%Y-%m', created_at)";
        $start = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $values = $query->toBase()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$month} as month, ".($sum === null ? 'count(*)' : "sum({$sum})").' as value')
            ->groupByRaw($month)
            ->pluck('value', 'month');

        return collect(range(self::MONTHS - 1, 0))
            ->mapWithKeys(function (int $offset) use ($start, $values): array {
                $month = $start->copy()->addMonths(self::MONTHS - 1 - $offset)->format('Y-m');

                return [$month => round((float) ($values[$month] ?? 0), 2)];
            })
            ->all();
    }

    /**
     * @param  array<string, float>  $monthly
     * @return array{series: list<array{month: string, value: float}>, current: float, previous: float, variation: float|null}
     */
    private function withVariation(array $monthly): array
    {
        $values = array_values($monthly);
        $current = $values[count($values) - 1];
        $previous = $values[count($values) - 2];

        return [
            'series' => array_map(fn (string $month, float $value): array => ['month' => $month, 'value' => $value], array_keys($monthly), $values),
            'current' => $current,
            'previous' => $previous,
            'variation' => $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null,
        ];
    }

    private function percentage(float|int $part, float|int $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
