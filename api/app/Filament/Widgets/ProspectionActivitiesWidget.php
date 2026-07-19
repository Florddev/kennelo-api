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
class ProspectionActivitiesWidget extends BaseWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Professionnels';

    protected ?string $description = 'Validation et suivi des activités inscrites sur la plateforme.';

    protected function skeletonView(): string
    {
        return 'filament.skeletons.stats-overview';
    }

    protected function skeletonData(): array
    {
        return ['count' => 3];
    }

    protected function getStats(): array
    {
        $activities = app(StatsService::class)->overview()['activities'];

        return [
            Stat::make('Professionnels validés', StatFormatter::number($activities['approved']))
                ->description(StatFormatter::number($activities['total']).' activités au total')
                ->color('success'),
            Stat::make('En attente de validation', StatFormatter::number($activities['pending']))
                ->description('À traiter dans « Professionnels »')
                ->color('warning'),
            Stat::make('Profils refusés', StatFormatter::number($activities['rejected']))
                ->description(StatFormatter::number($activities['professionals']).' pros avec SIRET')
                ->color('danger'),
        ];
    }

    protected function skeletonHeading(): ?string
    {
        return $this->getHeading();
    }

    protected function skeletonDescription(): ?string
    {
        return $this->getDescription();
    }
}
