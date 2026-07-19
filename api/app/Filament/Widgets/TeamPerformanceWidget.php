<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\Widget;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class TeamPerformanceWidget extends Widget
{
    use HasSkeleton;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.team-performance';

    protected function skeletonData(): array
    {
        return ['rows' => 4, 'variant' => 'row'];
    }

    protected function skeletonHeading(): ?string
    {
        return 'Performance par membre';
    }

    protected function skeletonDescription(): ?string
    {
        return 'Équipe commerciale';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'members' => app(StatsService::class)->business()['team_performance'],
        ];
    }
}
