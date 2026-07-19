<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\Widget;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class MarketOpportunitiesWidget extends Widget
{
    use HasSkeleton;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.market-opportunities';

    protected function skeletonData(): array
    {
        return ['rows' => 5, 'variant' => 'row'];
    }

    protected function skeletonHeading(): ?string
    {
        return 'Départements à fort potentiel';
    }

    protected function skeletonDescription(): ?string
    {
        return 'Forte demande utilisateurs, peu de professionnels inscrits : zones à démarcher en priorité.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'opportunities' => app(StatsService::class)->business()['market_coverage'],
        ];
    }
}
