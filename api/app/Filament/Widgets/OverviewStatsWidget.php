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
class OverviewStatsWidget extends BaseWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Vue d\'ensemble';

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
        $stats = app(StatsService::class);
        $finance = $stats->finance();
        $bookings = $stats->bookings();
        $community = $stats->community();

        $gmvTrend = StatFormatter::trend($finance['gmv_growth']['variation']);
        $mauTrend = StatFormatter::trend($community['user_growth']['variation']);

        return [
            Stat::make('Volume d\'affaires (GMV)', StatFormatter::euro($finance['gmv']))
                ->description($finance['paid_bookings'].' réservations payées · '.$gmvTrend['label'])
                ->descriptionIcon($gmvTrend['icon'])
                ->color($gmvTrend['color'])
                ->chart($this->series($finance['gmv_growth']['series'])),
            Stat::make('Revenu Kennelo', StatFormatter::euro($finance['kennelo_revenue']))
                ->description('Take rate : '.StatFormatter::percent($finance['take_rate']))
                ->color('success'),
            Stat::make('Réservations', StatFormatter::number($bookings['total']))
                ->description('Conversion paiement : '.StatFormatter::percent($bookings['payment_conversion_rate']))
                ->color('info'),
            Stat::make('Utilisateurs actifs (MAU)', StatFormatter::number($community['active_users']['mau']))
                ->description($community['active_users']['dau'].' aujourd\'hui · '.$community['active_users']['wau'].' cette semaine · '.$mauTrend['label'])
                ->descriptionIcon($mauTrend['icon'])
                ->color($mauTrend['color']),
        ];
    }

    /**
     * @param  array<int, array{month: string, total: float}>  $series
     * @return array<int, float>
     */
    private function series(array $series): array
    {
        return array_map(fn (array $point): float => $point['total'], $series);
    }

    protected function skeletonHeading(): ?string
    {
        return $this->getHeading();
    }
}
