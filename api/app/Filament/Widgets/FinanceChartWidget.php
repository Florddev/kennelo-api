<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Support\StatFormatter;
use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class FinanceChartWidget extends ChartWidget
{
    use HasSkeleton;

    protected ?string $heading = 'GMV mensuelle (6 derniers mois)';

    protected int|string|array $columnSpan = 'full';

    protected function skeletonView(): string
    {
        return 'filament.skeletons.chart';
    }

    protected function skeletonData(): array
    {
        return ['type' => 'line'];
    }

    protected function getData(): array
    {
        $series = app(StatsService::class)->finance()['gmv_growth']['series'];

        return [
            'datasets' => [
                [
                    'label' => 'GMV (€)',
                    'data' => array_map(fn (array $point): float => $point['total'], $series),
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(fn (array $point): string => StatFormatter::month($point['month']), $series),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                locale: 'fr-FR',
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ctx.parsed.y.toLocaleString('fr-FR') + ' €',
                        },
                    },
                },
                scales: {
                    y: {
                        ticks: {
                            callback: (value) => value.toLocaleString('fr-FR') + ' €',
                        },
                    },
                },
            }
        JS);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function skeletonHeading(): ?string
    {
        $heading = $this->getHeading();

        return $heading instanceof Htmlable ? $heading->toHtml() : $heading;
    }
}
