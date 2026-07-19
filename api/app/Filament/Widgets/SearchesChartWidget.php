<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Support\StatFormatter;
use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class SearchesChartWidget extends ChartWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Recherches par mois (12 derniers mois)';

    protected int|string|array $columnSpan = 1;

    protected function skeletonView(): string
    {
        return 'filament.skeletons.chart';
    }

    protected function skeletonData(): array
    {
        return ['type' => 'bar'];
    }

    protected function getData(): array
    {
        $monthly = app(StatsService::class)->searches()['monthly'];

        return [
            'datasets' => [
                [
                    'label' => 'Recherches',
                    'data' => array_map(fn (array $point): int => $point['total'], $monthly),
                    'backgroundColor' => '#059669',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_map(fn (array $point): string => StatFormatter::month($point['month']), $monthly),
        ];
    }

    protected function getOptions(): array
    {
        return ['locale' => 'fr-FR'];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function skeletonHeading(): ?string
    {
        $heading = $this->getHeading();

        return $heading instanceof Htmlable ? $heading->toHtml() : $heading;
    }
}
