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
class SearchQualityWidget extends BaseWidget
{
    use HasSkeleton;

    protected function skeletonView(): string
    {
        return 'filament.skeletons.stats-overview';
    }

    protected function skeletonData(): array
    {
        return ['count' => 1];
    }

    protected function getStats(): array
    {
        $searches = app(StatsService::class)->searches();

        return [
            Stat::make('Recherches sans résultat', StatFormatter::percent($searches['zero_result_rate']))
                ->description($searches['zero_result_count'].' sur '.$searches['total'].' recherches')
                ->color('warning'),
        ];
    }
}
