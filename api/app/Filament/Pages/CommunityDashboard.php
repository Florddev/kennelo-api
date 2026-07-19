<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\CommunityChartWidget;
use App\Filament\Widgets\CommunityEngagementWidget;
use App\Filament\Widgets\CommunityStatsWidget;
use App\Filament\Widgets\RatingDistributionWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CommunityDashboard extends BaseDashboard
{
    protected static string $routePath = 'communaute';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Communauté';

    protected static ?string $title = 'Communauté';

    protected static ?int $navigationSort = 4;

    public function getSubheading(): ?string
    {
        return 'Croissance, engagement et satisfaction des utilisateurs.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            CommunityStatsWidget::class,
            CommunityChartWidget::class,
            RatingDistributionWidget::class,
            CommunityEngagementWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
