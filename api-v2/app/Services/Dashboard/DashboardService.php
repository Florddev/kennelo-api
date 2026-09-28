<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\BookingModeEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\Booking;
use App\Models\Organization;
use App\Models\Review;
use App\Services\Review\ReviewService;
use App\Services\Stay\UnitTypeService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord d'une activité, ou de toute l'entreprise : la journée (arrivées, départs, rendez-vous,
 * animaux confiés), les demandes à traiter, les prochaines arrivées, l'occupation des places, la note, et le
 * chiffre d'affaires pour ceux qui voient les finances.
 *
 * Le chiffre d'affaires d'un mois est ce que rapportent à l'entreprise les réservations qui commencent ce
 * mois-là : leur montant net versé (activity_amount), remboursements et commission déduits.
 */
class DashboardService
{
    private const int REVENUE_MONTHS = 6;

    private const int UPCOMING = 5;

    public function __construct(
        private readonly UnitTypeService $unitTypes,
        private readonly ReviewService $reviews,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forActivity(Activity $activity, bool $withRevenue): array
    {
        return $this->build($activity->newCollection([$activity->loadMissing('profession')]), $activity->timezone, $withRevenue);
    }

    /**
     * @return array<string, mixed>
     */
    public function forOrganization(Organization $organization, bool $withRevenue): array
    {
        return $this->build(
            $organization->activities()->with('profession')->get(),
            (string) config('activities.default_timezone'),
            $withRevenue,
        );
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<string, mixed>
     */
    private function build(Collection $activities, string $timezone, bool $withRevenue): array
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $ids = $activities->modelKeys();
        $bookings = fn (): Builder => Booking::query()->whereIn('bookings.activity_id', $ids);
        $ofMode = fn (BookingModeEnum $mode): Builder => $bookings()->whereHas('activity.profession', fn (Builder $profession) => $profession->where('booking_mode', $mode));
        $active = [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS];

        return [
            'date' => $today->toDateString(),
            'today' => [
                'arrivals' => $ofMode(BookingModeEnum::STAY)->whereIn('status', $active)->whereDate('start_date', $today)->count(),
                'departures' => $ofMode(BookingModeEnum::STAY)->whereIn('status', [...$active, BookingStatusEnum::COMPLETED])->whereDate('end_date', $today)->count(),
                'appointments' => $ofMode(BookingModeEnum::APPOINTMENT)->whereIn('status', [...$active, BookingStatusEnum::COMPLETED])->whereDate('start_date', $today)->count(),
                'pets_in_care' => DB::table('booking_pets')
                    ->join('bookings', 'bookings.id', '=', 'booking_pets.booking_id')
                    ->whereIn('bookings.activity_id', $ids)
                    ->whereIn('bookings.status', $active)
                    ->whereDate('bookings.start_date', '<=', $today)
                    ->whereDate('bookings.end_date', '>=', $today)
                    ->distinct()
                    ->count('booking_pets.pet_id'),
            ],
            'pending_requests' => $bookings()->where('status', BookingStatusEnum::PENDING)->count(),
            'upcoming' => $bookings()
                ->where('status', BookingStatusEnum::CONFIRMED)
                ->whereDate('start_date', '>=', $today)
                ->with(['activity', 'user'])
                ->orderBy('start_date')
                ->orderBy('id')
                ->limit(self::UPCOMING)
                ->get()
                ->map(fn (Booking $booking): array => [
                    'id' => $booking->id,
                    'activity' => ['id' => $booking->activity_id, 'name' => $booking->activity?->name],
                    'client' => ['id' => $booking->user_id, 'first_name' => $booking->user?->first_name, 'last_name' => $booking->user?->last_name],
                    'start_date' => $booking->start_date->toDateString(),
                    'end_date' => $booking->end_date->toDateString(),
                ])
                ->all(),
            'occupancy' => $this->occupancy($activities, $today),
            'rating' => $this->reviews->rating(Review::query()->byClients()->published()->whereIn('activity_id', $ids)),
            'revenue' => $withRevenue ? $this->revenue($bookings(), $today) : null,
        ];
    }

    /**
     * Places occupées aujourd'hui, par type de place des activités de séjour.
     *
     * @param  Collection<int, Activity>  $activities
     * @return list<array<string, mixed>>
     */
    private function occupancy(Collection $activities, CarbonImmutable $today): array
    {
        // Les dates des séjours se comparent sans fuseau, comme dans le calendrier des prix.
        $day = CarbonImmutable::parse($today->toDateString());
        $rows = [];

        foreach ($activities as $activity) {
            if ($activity->profession?->booking_mode !== BookingModeEnum::STAY) {
                continue;
            }

            $occupied = $this->unitTypes->occupancy($activity, $activity->profession->billing_unit, $day, $day);

            foreach ($this->unitTypes->forActivity($activity) as $unitType) {
                /** @var ActivityUnitType $unitType */
                $taken = $occupied[$unitType->id][$day->toDateString()] ?? 0;

                $rows[] = [
                    'activity_id' => $activity->id,
                    'unit_type' => ['id' => $unitType->id, 'name' => $unitType->name],
                    'capacity' => $unitType->quantity,
                    'occupied' => $taken,
                    'available' => max(0, $unitType->quantity - $taken),
                    'rate' => $unitType->quantity > 0 ? round($taken / $unitType->quantity * 100, 1) : 0.0,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  Builder<Booking>  $bookings
     * @return array{currency: string, current_month: string, previous_month: string, change_rate: float|null, series: list<array{month: string, amount: string}>}
     */
    private function revenue(Builder $bookings, CarbonImmutable $today): array
    {
        $months = collect(range(self::REVENUE_MONTHS - 1, 0))->map(fn (int $offset): string => $today->startOfMonth()->subMonths($offset)->format('Y-m'));
        $amounts = $months->mapWithKeys(fn (string $month): array => [$month => '0.00'])->all();

        $bookings
            ->whereIn('status', [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS, BookingStatusEnum::COMPLETED, BookingStatusEnum::CANCELLED])
            ->whereDate('start_date', '>=', $today->startOfMonth()->subMonths(self::REVENUE_MONTHS - 1))
            ->whereDate('start_date', '<=', $today->endOfMonth())
            ->get(['start_date', 'activity_amount'])
            ->each(function (Booking $booking) use (&$amounts): void {
                $month = $booking->start_date->format('Y-m');
                $amounts[$month] = Money::sum($amounts[$month] ?? '0', $booking->activity_amount);
            });

        $current = $amounts[$months->last()];
        $previous = $amounts[$months->get(self::REVENUE_MONTHS - 2)];

        return [
            'currency' => 'EUR',
            'current_month' => $current,
            'previous_month' => $previous,
            'change_rate' => bccomp($previous, '0', 2) > 0 ? round(((float) $current - (float) $previous) / (float) $previous * 100, 1) : null,
            'series' => $months->map(fn (string $month): array => ['month' => $month, 'amount' => $amounts[$month]])->values()->all(),
        ];
    }
}
