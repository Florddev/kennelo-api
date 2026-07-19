<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class SearchDepartmentsChartWidget extends ChartWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Top départements';

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
        $byDepartment = array_slice(app(StatsService::class)->searches()['by_department'], 0, 12);

        return [
            'datasets' => [
                [
                    'label' => 'Recherches',
                    'data' => array_map(fn (array $point): int => (int) $point['total'], $byDepartment),
                    'backgroundColor' => '#0284c7',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_map(fn (array $point): string => (string) $point['department'], $byDepartment),
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
