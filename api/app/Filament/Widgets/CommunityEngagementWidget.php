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
class CommunityEngagementWidget extends BaseWidget
{
    use HasSkeleton;

    protected function skeletonView(): string
    {
        return 'filament.skeletons.stats-overview';
    }

    protected function skeletonData(): array
    {
        return ['count' => 2];
    }

    protected function getStats(): array
    {
        $community = app(StatsService::class)->community();

        return [
            Stat::make('Messages (30 derniers jours)', StatFormatter::number($community['messages_last_30_days']))
                ->color('info'),
            Stat::make('Conversations actives', StatFormatter::number($community['active_conversations']))
                ->color('primary'),
        ];
    }
}
