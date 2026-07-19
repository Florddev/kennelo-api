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
class ProspectionProspectsWidget extends BaseWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Prospection';

    protected ?string $description = 'Découverte commerciale et conversion des prospects.';

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
        $prospects = app(StatsService::class)->overview()['prospects'];

        return [
            Stat::make('Prospects identifiés', StatFormatter::number($prospects['total']))
                ->description('Établissements découverts via Apify')
                ->color('primary'),
            Stat::make('Déjà inscrits sur Kennelo', StatFormatter::number($prospects['registered']))
                ->description('Rapprochés à un professionnel')
                ->color('info'),
            Stat::make('Inscriptions via prospection', StatFormatter::number($prospects['from_prospection']))
                ->description('Prospects passés au statut « Inscrit »')
                ->color('success'),
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
