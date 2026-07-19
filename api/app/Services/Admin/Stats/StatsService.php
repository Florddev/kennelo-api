<?php

declare(strict_types=1);

namespace App\Services\Admin\Stats;

use App\Enums\ActivityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProspectContactTypeEnum;
use App\Enums\ProspectStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Prospect;
use App\Models\ProspectContact;
use App\Models\ProspectImport;
use App\Models\Review;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StatsService
{
    private const CACHE_TTL = 120;

    public function overview(): array
    {
        return Cache::remember('admin:stats:overview', self::CACHE_TTL, function (): array {
            $prospectsByStatus = Prospect::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all();

            return [
                'activities' => [
                    'total' => Activity::count(),
                    'approved' => Activity::where('status', ActivityStatusEnum::APPROVED->value)->count(),
                    'pending' => Activity::where('status', ActivityStatusEnum::PENDING->value)->count(),
                    'rejected' => Activity::where('status', ActivityStatusEnum::REJECTED->value)->count(),
                    'professionals' => Activity::whereNotNull('siret')->count(),
                ],
                'prospects' => [
                    'total' => Prospect::count(),
                    'registered' => Prospect::whereNotNull('kennelo_activity_id')->count(),
                    'by_status' => $this->fillProspectStatuses($prospectsByStatus),
                    'from_prospection' => Prospect::where('status', ProspectStatusEnum::INSCRIT->value)->count(),
                ],
                'searches' => [
                    'total' => SearchLog::count(),
                    'last_30_days' => SearchLog::where('created_at', '>=', now()->subDays(30))->count(),
                ],
            ];
        });
    }

    public function searches(): array
    {
        return Cache::remember('admin:stats:searches', self::CACHE_TTL, function (): array {
            $byDepartment = SearchLog::query()
                ->whereNotNull('department')
                ->selectRaw('department, count(*) as total')
                ->groupBy('department')
                ->orderByDesc('total')
                ->limit(30)
                ->pluck('total', 'department')
                ->map(fn ($total, $department): array => [
                    'department' => (string) $department,
                    'total' => (int) $total,
                ])
                ->values()
                ->all();

            $monthly = SearchLog::query()
                ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
                ->get(['created_at'])
                ->groupBy(fn ($log): string => $log->created_at->format('Y-m'))
                ->map(fn ($group): int => $group->count())
                ->sortKeys()
                ->map(fn (int $total, string $month): array => ['month' => $month, 'total' => $total])
                ->values()
                ->all();

            $totalSearches = SearchLog::query()->count();
            $zeroResults = SearchLog::query()->where('results_count', 0)->count();

            return [
                'by_department' => $byDepartment,
                'monthly' => $monthly,
                'total' => $totalSearches,
                'zero_result_count' => $zeroResults,
                'zero_result_rate' => $this->percentage($zeroResults, $totalSearches),
                'top_filters' => $this->topFilters(),
            ];
        });
    }

    public function business(): array
    {
        return Cache::remember('admin:stats:business', self::CACHE_TTL, fn (): array => [
            'prospection_funnel' => $this->prospectionFunnel(),
            'validation' => $this->validationStats(),
            'market_coverage' => $this->marketCoverage(),
            'growth' => $this->growth(),
            'team_performance' => $this->teamPerformance(),
            'contact_type_mix' => $this->contactTypeMix(),
        ]);
    }

    public function finance(): array
    {
        return Cache::remember('admin:stats:finance', self::CACHE_TTL, function (): array {
            $paid = Booking::query()->where('payment_status', PaymentStatusEnum::SUCCEEDED->value);

            $gmv = (float) (clone $paid)->sum('total_price');
            $revenue = (float) (clone $paid)->sum('service_fee') + (float) (clone $paid)->sum('platform_fee');
            $netToPros = (float) (clone $paid)->sum('activity_amount');
            $refunds = (float) Booking::query()->sum('refunded_amount');
            $paidCount = (clone $paid)->count();

            $monthly = $this->monthlyCounts((clone $paid), $this->recentMonths(6), 'total_price');

            return [
                'gmv' => round($gmv, 2),
                'kennelo_revenue' => round($revenue, 2),
                'take_rate' => $gmv > 0 ? round(($revenue / $gmv) * 100, 1) : 0.0,
                'net_to_pros' => round($netToPros, 2),
                'refunds' => round($refunds, 2),
                'refund_rate' => $gmv > 0 ? round(($refunds / $gmv) * 100, 1) : 0.0,
                'avg_basket' => $paidCount > 0 ? round($gmv / $paidCount, 2) : 0.0,
                'paid_bookings' => $paidCount,
                'gmv_growth' => $this->withVariation($monthly),
            ];
        });
    }

    public function bookings(): array
    {
        return Cache::remember('admin:stats:bookings', self::CACHE_TTL, function (): array {
            $counts = Booking::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $total = (int) $counts->sum();
            $cancelled = (int) ($counts[BookingStatusEnum::CANCELLED->value] ?? 0)
                + (int) ($counts[BookingStatusEnum::REJECTED->value] ?? 0)
                + (int) ($counts[BookingStatusEnum::EXPIRED->value] ?? 0);
            $paid = Booking::query()->where('payment_status', PaymentStatusEnum::SUCCEEDED->value)->count();

            $avgNights = Booking::query()
                ->whereNotNull('check_in_date')
                ->whereNotNull('check_out_date')
                ->get(['check_in_date', 'check_out_date'])
                ->map(fn (Booking $booking): float => $booking->check_in_date->diffInDays($booking->check_out_date))
                ->avg();

            return [
                'total' => $total,
                'by_status' => $this->fillBookingStatuses($counts->all()),
                'cancellation_rate' => $this->percentage($cancelled, $total),
                'payment_conversion_rate' => $this->percentage($paid, $total),
                'avg_stay_nights' => $avgNights !== null ? round((float) $avgNights, 1) : null,
                'monthly' => $this->withVariation($this->monthlyCounts(Booking::query(), $this->recentMonths(6))),
            ];
        });
    }

    public function community(): array
    {
        return Cache::remember('admin:stats:community', self::CACHE_TTL, function (): array {
            $userCounts = User::withInactive()->withTrashed()
                ->selectRaw('count(*) as total')
                ->selectRaw('sum(case when is_id_verified = 1 then 1 else 0 end) as verified_id')
                ->selectRaw('sum(case when email_verified_at is not null then 1 else 0 end) as verified_email')
                ->toBase()
                ->first();

            $totalUsers = (int) $userCounts->total;
            $verifiedId = (int) $userCounts->verified_id;
            $verifiedEmail = (int) $userCounts->verified_email;

            $pros = User::withInactive()->withTrashed()->role('manager')->count();
            $admins = User::withInactive()->withTrashed()->role('admin')->count();
            $owners = max($totalUsers - $pros - $admins, 0);

            $activeCounts = User::withInactive()->withTrashed()
                ->selectRaw('sum(case when last_seen_at >= ? then 1 else 0 end) as dau', [now()->subDay()])
                ->selectRaw('sum(case when last_seen_at >= ? then 1 else 0 end) as wau', [now()->subWeek()])
                ->selectRaw('sum(case when last_seen_at >= ? then 1 else 0 end) as mau', [now()->subMonth()])
                ->toBase()
                ->first();

            $avgRating = Review::query()
                ->where('reviewer_type', ReviewerTypeEnum::USER->value)
                ->where('is_published', true)
                ->avg('overall_rating');

            $publishedUserReviews = Review::query()
                ->where('reviewer_type', ReviewerTypeEnum::USER->value)
                ->where('is_published', true);

            $reviewsTotal = (clone $publishedUserReviews)->count();
            $reviewsAnswered = (clone $publishedUserReviews)->whereHas('response')->count();

            return [
                'user_growth' => $this->withVariation(
                    $this->monthlyCounts(User::withInactive()->withTrashed(), $this->recentMonths(6)),
                ),
                'roles_split' => [
                    'owners' => $owners,
                    'pros' => $pros,
                    'admins' => $admins,
                ],
                'kyc_rate' => $this->percentage($verifiedId, $totalUsers),
                'email_verified_rate' => $this->percentage($verifiedEmail, $totalUsers),
                'active_users' => [
                    'dau' => (int) $activeCounts->dau,
                    'wau' => (int) $activeCounts->wau,
                    'mau' => (int) $activeCounts->mau,
                ],
                'avg_pro_rating' => $avgRating !== null ? round((float) $avgRating, 2) : null,
                'rating_distribution' => $this->ratingDistribution(),
                'review_response_rate' => $this->percentage($reviewsAnswered, $reviewsTotal),
                'messages_last_30_days' => Message::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'active_conversations' => Conversation::query()->where('last_message_at', '>=', now()->subDays(30))->count(),
            ];
        });
    }

    private function fillBookingStatuses(array $counts): array
    {
        $result = [];

        foreach (BookingStatusEnum::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    private function ratingDistribution(): array
    {
        $counts = Review::query()
            ->where('reviewer_type', ReviewerTypeEnum::USER->value)
            ->where('is_published', true)
            ->selectRaw('round(overall_rating) as bucket, count(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $result = [];

        for ($star = 1; $star <= 5; $star++) {
            $result[$star] = (int) ($counts[$star] ?? 0);
        }

        return $result;
    }

    private function teamPerformance(): array
    {
        $contactsByMember = ProspectContact::query()
            ->selectRaw('author_id, count(*) as total')
            ->whereNotNull('author_id')
            ->groupBy('author_id')
            ->pluck('total', 'author_id');

        $conversionsByMember = Prospect::query()
            ->selectRaw('assigned_to, count(*) as total')
            ->whereNotNull('assigned_to')
            ->where('status', ProspectStatusEnum::INSCRIT->value)
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $importsByMember = ProspectImport::query()
            ->selectRaw('requested_by, sum(imported_count) as total')
            ->whereNotNull('requested_by')
            ->groupBy('requested_by')
            ->pluck('total', 'requested_by');

        $memberIds = collect($contactsByMember->keys())
            ->merge($conversionsByMember->keys())
            ->merge($importsByMember->keys())
            ->unique()
            ->values();

        $names = User::withInactive()->withTrashed()
            ->whereIn('id', $memberIds)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user): array => [$user->id => trim("{$user->first_name} {$user->last_name}")]);

        return $memberIds
            ->map(fn (string $memberId): array => [
                'member' => $names[$memberId] ?? 'Inconnu',
                'contacts' => (int) ($contactsByMember[$memberId] ?? 0),
                'conversions' => (int) ($conversionsByMember[$memberId] ?? 0),
                'imported' => (int) ($importsByMember[$memberId] ?? 0),
            ])
            ->sortByDesc('conversions')
            ->values()
            ->take(10)
            ->all();
    }

    private function contactTypeMix(): array
    {
        $counts = ProspectContact::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $result = [];

        foreach (ProspectContactTypeEnum::cases() as $type) {
            $result[$type->value] = (int) ($counts[$type->value] ?? 0);
        }

        return $result;
    }

    private function topFilters(): array
    {
        return SearchLog::query()
            ->whereNotNull('filters')
            ->pluck('filters')
            ->flatMap(fn ($filters): array => is_array($filters) ? array_keys($filters) : [])
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn (int $total, string $filter): array => ['filter' => $filter, 'total' => $total])
            ->values()
            ->all();
    }

    private function prospectionFunnel(): array
    {
        $counts = Prospect::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $identified = (int) $counts->sum();
        $contacted = (int) $counts->except([ProspectStatusEnum::NON_CONTACTE->value])->sum();
        $registered = (int) ($counts[ProspectStatusEnum::INSCRIT->value] ?? 0);
        $refused = (int) ($counts[ProspectStatusEnum::REFUSE->value] ?? 0);

        return [
            'identified' => $identified,
            'contacted' => $contacted,
            'registered' => $registered,
            'refused' => $refused,
            'contact_rate' => $this->percentage($contacted, $identified),
            'conversion_rate' => $this->percentage($registered, $contacted),
            'overall_conversion_rate' => $this->percentage($registered, $identified),
        ];
    }

    private function validationStats(): array
    {
        $approved = Activity::where('status', ActivityStatusEnum::APPROVED->value)->count();
        $rejected = Activity::where('status', ActivityStatusEnum::REJECTED->value)->count();
        $pending = Activity::where('status', ActivityStatusEnum::PENDING->value)->count();
        $reviewed = $approved + $rejected;

        $avgProcessingDays = Activity::query()
            ->whereNotNull('reviewed_at')
            ->get(['created_at', 'reviewed_at'])
            ->map(fn ($activity): float => $activity->created_at->diffInDays($activity->reviewed_at))
            ->avg();

        return [
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'approval_rate' => $this->percentage($approved, $reviewed),
            'avg_processing_days' => $avgProcessingDays !== null ? round((float) $avgProcessingDays, 1) : null,
        ];
    }

    private function marketCoverage(): array
    {
        $searchesByDept = SearchLog::query()
            ->whereNotNull('department')
            ->selectRaw('department, count(*) as total')
            ->groupBy('department')
            ->pluck('total', 'department');

        $prosByDept = Activity::query()
            ->whereNotNull('siret')
            ->whereHas('address', fn ($q) => $q->whereNotNull('department'))
            ->with('address:id,department')
            ->get()
            ->groupBy(fn (Activity $activity): ?string => $activity->address?->department)
            ->map(fn ($group): int => $group->count());

        return $searchesByDept
            ->map(fn ($searches, $department): array => [
                'department' => (string) $department,
                'searches' => (int) $searches,
                'professionals' => (int) ($prosByDept[$department] ?? 0),
                'opportunity_score' => (int) $searches - (int) ($prosByDept[$department] ?? 0) * 5,
            ])
            ->sortByDesc('opportunity_score')
            ->values()
            ->take(10)
            ->all();
    }

    private function growth(): array
    {
        $months = $this->recentMonths(6);

        $searches = $this->monthlyCounts(SearchLog::query(), $months);
        $prospects = $this->monthlyCounts(Prospect::query(), $months);
        $registrations = $this->monthlyCounts(
            Prospect::query()->where('status', ProspectStatusEnum::INSCRIT->value),
            $months,
        );

        return [
            'searches' => $this->withVariation($searches),
            'prospects' => $this->withVariation($prospects),
            'registrations' => $this->withVariation($registrations),
        ];
    }

    private function recentMonths(int $count): Collection
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $offset): string => now()->subMonths($offset)->format('Y-m'));
    }

    private function monthlyCounts(Builder $query, Collection $months, ?string $sumColumn = null): array
    {
        $rows = $query
            ->where('created_at', '>=', now()->subMonths($months->count())->startOfMonth())
            ->get($sumColumn === null ? ['created_at'] : ['created_at', $sumColumn]);

        $buckets = $rows
            ->groupBy(fn ($row): string => Carbon::parse($row->getAttribute('created_at'))->format('Y-m'))
            ->map(fn (Collection $group): float => $sumColumn === null
                ? (float) $group->count()
                : (float) $group->sum($sumColumn));

        return $months->mapWithKeys(fn (string $month): array => [
            $month => round((float) ($buckets[$month] ?? 0), 2),
        ])->all();
    }

    private function withVariation(array $monthly): array
    {
        $values = array_values($monthly);
        $current = end($values) ?: 0;
        $previous = count($values) >= 2 ? $values[count($values) - 2] : 0;

        return [
            'series' => collect($monthly)
                ->map(fn (float $total, string $month): array => ['month' => $month, 'total' => $total])
                ->values()
                ->all(),
            'current' => $current,
            'previous' => $previous,
            'variation' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null,
        ];
    }

    private function percentage(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }

    private function fillProspectStatuses(array $counts): array
    {
        $result = [];

        foreach (ProspectStatusEnum::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }
}
