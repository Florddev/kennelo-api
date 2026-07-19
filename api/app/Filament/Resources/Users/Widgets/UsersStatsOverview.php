<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Widgets;

use App\Enums\UserStatusEnum;
use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class UsersStatsOverview extends BaseWidget
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
        $counts = User::withInactive()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active', [UserStatusEnum::ACTIVE->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as banned', [UserStatusEnum::BANNED->value])
            ->selectRaw('sum(case when email_verified_at is not null then 1 else 0 end) as verified')
            ->toBase()
            ->first();

        return [
            Stat::make('Total', (string) (int) $counts->total)
                ->description('Utilisateurs inscrits')
                ->color('primary'),
            Stat::make('Actifs', (string) (int) $counts->active)
                ->description('Comptes actifs')
                ->color('success'),
            Stat::make('Bannis', (string) (int) $counts->banned)
                ->description('Comptes bannis')
                ->color('danger'),
            Stat::make('Vérifiés', (string) (int) $counts->verified)
                ->description('E-mail vérifié')
                ->color('info'),
        ];
    }
}
