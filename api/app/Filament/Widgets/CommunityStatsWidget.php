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
class CommunityStatsWidget extends BaseWidget
{
    use HasSkeleton;

    protected function skeletonView(): string
    {
        return 'filament.skeletons.stats-overview';
    }

    protected function skeletonData(): array
    {
        return ['count' => 4];
    }

    protected function getStats(): array
    {
        $community = app(StatsService::class)->community();

        return [
            Stat::make('Croissance ce mois', StatFormatter::number($community['user_growth']['current']))
                ->description($community['user_growth']['previous'].' le mois précédent')
                ->color('primary'),
            Stat::make('Actifs (30 j)', StatFormatter::number($community['active_users']['mau']))
                ->description($community['active_users']['wau'].' / 7j · '.$community['active_users']['dau'].' / 24h')
                ->color('info'),
            Stat::make('Taux de vérification KYC', StatFormatter::percent($community['kyc_rate']))
                ->description('Email vérifié : '.StatFormatter::percent($community['email_verified_rate']))
                ->color('success'),
            Stat::make('Note moyenne des pros', StatFormatter::number($community['avg_pro_rating'] ?? 0, 2))
                ->description('Réponse aux avis : '.StatFormatter::percent($community['review_response_rate']))
                ->color('warning'),
        ];
    }
}
