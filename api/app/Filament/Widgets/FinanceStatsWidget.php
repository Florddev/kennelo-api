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
class FinanceStatsWidget extends BaseWidget
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
        $finance = app(StatsService::class)->finance();
        $gmvTrend = StatFormatter::trend($finance['gmv_growth']['variation']);

        return [
            Stat::make('Volume d\'affaires (GMV)', StatFormatter::euro($finance['gmv']))
                ->description($finance['paid_bookings'].' réservations payées · '.$gmvTrend['label'])
                ->descriptionIcon($gmvTrend['icon'])
                ->color($gmvTrend['color']),
            Stat::make('Revenu Kennelo', StatFormatter::euro($finance['kennelo_revenue']))
                ->description('Take rate : '.StatFormatter::percent($finance['take_rate']))
                ->color('success'),
            Stat::make('Panier moyen', StatFormatter::euro($finance['avg_basket']))
                ->description('Reversé aux pros : '.StatFormatter::euro($finance['net_to_pros']))
                ->color('info'),
            Stat::make('Remboursements', StatFormatter::euro($finance['refunds']))
                ->description('Taux : '.StatFormatter::percent($finance['refund_rate']))
                ->color('warning'),
        ];
    }
}
