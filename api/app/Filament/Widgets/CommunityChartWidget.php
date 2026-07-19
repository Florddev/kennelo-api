<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class CommunityChartWidget extends ChartWidget
{
    use HasSkeleton;

    protected ?string $heading = 'Répartition des utilisateurs';

    protected int|string|array $columnSpan = 1;

    protected function skeletonView(): string
    {
        return 'filament.skeletons.chart';
    }

    protected function skeletonData(): array
    {
        return ['type' => 'doughnut'];
    }

    protected function getData(): array
    {
        $roles = app(StatsService::class)->community()['roles_split'];

        return [
            'datasets' => [
                [
                    'label' => 'Utilisateurs',
                    'data' => [$roles['owners'], $roles['pros'], $roles['admins']],
                    'backgroundColor' => ['#059669', '#0284c7', '#f59e0b'],
                ],
            ],
            'labels' => ['Propriétaires', 'Professionnels', 'Admins'],
        ];
    }

    protected function getOptions(): array
    {
        return ['locale' => 'fr-FR'];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function skeletonHeading(): ?string
    {
        $heading = $this->getHeading();

        return $heading instanceof Htmlable ? $heading->toHtml() : $heading;
    }
}
