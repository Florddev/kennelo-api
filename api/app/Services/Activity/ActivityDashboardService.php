<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\AvailabilityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\ActivityCycleSetting;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class ActivityDashboardService
{
    private const REVENUE_MONTHS = 6;

    public function __construct(private ActivityCycleService $cycleService) {}

    public function getDashboard(Activity $activity): array
    {
        $today = now()->toDateString();

        $settings = $this->cycleService->getSettingsWithOccupancy($activity, $today);

        $totalCapacity = (int) $settings->sum('max_capacity');
        $totalOccupied = (int) $settings->sum('occupied_spots');

        $todayStatus = ActivityAvailability::where('activity_id', $activity->id)
            ->where('date', $today)
            ->value('status') ?? AvailabilityStatusEnum::OPEN->value;

        $occupancyByAnimal = $settings->map(function (ActivityCycleSetting $setting): array {
            $occupiedSpots = $setting->occupied_spots;

            return [
                'animal_type' => [
                    'id' => $setting->animalType->id,
                    'code' => $setting->animalType->code,
                    'name' => $setting->animalType->name,
                    'category' => $setting->animalType->category,
                ],
                'max_capacity' => $setting->max_capacity,
                'occupied_spots' => $occupiedSpots,
                'available_spots' => $setting->max_capacity - $occupiedSpots,
                'occupancy_rate' => $setting->max_capacity > 0
                    ? round($occupiedSpots / $setting->max_capacity * 100, 1)
                    : 0.0,
            ];
        })->values()->all();

        return [
            'summary' => [
                'total_capacity' => $totalCapacity,
                'occupied_spots' => $totalOccupied,
                'available_spots' => $totalCapacity - $totalOccupied,
                'today_status' => $todayStatus,
            ],
            'occupancy_by_animal' => $occupancyByAnimal,
            'revenue' => $this->revenue($activity),
            'upcoming_bookings' => [],
        ];
    }

    /**
     * @return array{current_month: float, previous_month: float, change_rate: float|null, currency: string, series: array<int, array{month: string, amount: float}>}
     */
    private function revenue(Activity $activity): array
    {
        $rangeStart = now()->startOfMonth()->subMonths(self::REVENUE_MONTHS - 1);

        $bookings = Booking::where('activity_id', $activity->id)
            ->whereNotNull('paid_at')
            ->whereIn('status', [
                BookingStatusEnum::CONFIRMED->value,
                BookingStatusEnum::IN_PROGRESS->value,
                BookingStatusEnum::COMPLETED->value,
            ])
            ->where('paid_at', '>=', $rangeStart)
            ->get(['activity_amount', 'paid_at']);

        $buckets = [];
        for ($i = self::REVENUE_MONTHS - 1; $i >= 0; $i--) {
            $buckets[now()->startOfMonth()->subMonths($i)->format('Y-m')] = 0.0;
        }

        foreach ($bookings as $booking) {
            $key = Carbon::parse($booking->paid_at)->format('Y-m');
            if (array_key_exists($key, $buckets)) {
                $buckets[$key] += (float) $booking->activity_amount;
            }
        }

        $currentKey = now()->format('Y-m');
        $previousKey = now()->startOfMonth()->subMonthNoOverflow()->format('Y-m');
        $current = $buckets[$currentKey] ?? 0.0;
        $previous = $buckets[$previousKey] ?? 0.0;

        return [
            'current_month' => round($current, 2),
            'previous_month' => round($previous, 2),
            'change_rate' => $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null,
            'currency' => (string) config('services.stripe.currency', 'eur'),
            'series' => collect($buckets)
                ->map(static fn (float $amount, string $month): array => [
                    'month' => $month,
                    'amount' => round($amount, 2),
                ])
                ->values()
                ->all(),
        ];
    }
}
