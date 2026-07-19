<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Support\StatFormatter;
use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class BookingsStatsWidget extends BaseWidget
{
    use HasSkeleton;

    protected function skeletonView(): string
    {
        return 'filament.skeletons.stats-overview';
    }

    protected function skeletonData(): array
    {
        return ['count' => 4];
    }

    protected function getStats(): array
    {
        $bookings = app(StatsService::class)->bookings();

        return [
            Stat::make('Réservations totales', StatFormatter::number($bookings['total']))
                ->description('Tous statuts confondus')
                ->color('primary'),
            Stat::make('Taux d\'annulation', StatFormatter::percent($bookings['cancellation_rate']))
                ->description('Annulées / refusées / expirées')
                ->color('danger'),
            Stat::make('Taux de conversion paiement', StatFormatter::percent($bookings['payment_conversion_rate']))
                ->description('Payées / créées')
                ->color('success'),
            Stat::make('Durée moyenne de séjour', StatFormatter::number($bookings['avg_stay_nights'] ?? 0, 1))
                ->description('Nuits par réservation')
                ->color('info'),
        ];
    }
}
