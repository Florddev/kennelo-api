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
class BusinessKpiWidget extends BaseWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Indicateurs clés';

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
        $business = app(StatsService::class)->business();
        $funnel = $business['prospection_funnel'];
        $validation = $business['validation'];
        $registrations = $business['growth']['registrations'];

        $validationDesc = $validation['avg_processing_days'] !== null
            ? 'Traitement moyen : '.StatFormatter::number($validation['avg_processing_days'], 1).' j · '.$validation['pending'].' en attente'
            : $validation['pending'].' en attente de traitement';

        $regTrend = StatFormatter::trend($registrations['variation']);

        return [
            Stat::make('Taux de conversion prospection', StatFormatter::percent($funnel['overall_conversion_rate']))
                ->description($funnel['registered'].' inscrits sur '.$funnel['identified'].' prospects identifiés')
                ->color('primary'),
            Stat::make('Taux de validation des pros', StatFormatter::percent($validation['approval_rate']))
                ->description($validationDesc)
                ->color('success'),
            Stat::make('Inscriptions ce mois-ci', StatFormatter::number($registrations['current']))
                ->description($registrations['previous'].' le mois précédent · '.$regTrend['label'])
                ->descriptionIcon($regTrend['icon'])
                ->color($regTrend['color']),
        ];
    }

    protected function skeletonHeading(): ?string
    {
        return $this->getHeading();
    }
}
