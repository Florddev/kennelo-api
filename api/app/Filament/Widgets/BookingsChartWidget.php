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
class BookingsChartWidget extends ChartWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Réservations par mois';

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
        $series = app(StatsService::class)->bookings()['monthly']['series'];

        return [
            'datasets' => [
                [
                    'label' => 'Réservations',
                    'data' => array_map(fn (array $point): float => $point['total'], $series),
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_map(fn (array $point): string => StatFormatter::month($point['month']), $series),
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
