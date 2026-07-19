<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Widgets;

use App\Enums\ProspectStatusEnum;
use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class ProspectsStatsOverview extends BaseWidget
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
        $counts = Prospect::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when kennelo_activity_id is not null then 1 else 0 end) as registered')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as inscrits', [ProspectStatusEnum::INSCRIT->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as non_contacted', [ProspectStatusEnum::NON_CONTACTE->value])
            ->toBase()
            ->first();

        return [
            Stat::make('Total', (string) (int) $counts->total)
                ->description('Prospects identifiés')
                ->color('primary'),
            Stat::make('Déjà inscrits', (string) (int) $counts->registered)
                ->description('Rapprochés d\'une activité Kennelo')
                ->color('success'),
            Stat::make('Inscrits (prospection)', (string) (int) $counts->inscrits)
                ->description('Statut « Inscrit »')
                ->color('info'),
            Stat::make('Non contactés', (string) (int) $counts->non_contacted)
                ->description('À traiter')
                ->color('gray'),
        ];
    }
}
