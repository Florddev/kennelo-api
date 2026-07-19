<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\BusinessKpiWidget;
use App\Filament\Widgets\MarketOpportunitiesWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\ProspectionFunnelWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|UnitEnum|null $navigationGroup = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Vue d\'ensemble';

    protected static ?string $title = 'Vue d\'ensemble';

    protected static ?int $navigationSort = 1;

    public function getSubheading(): ?string
    {
        return 'Santé globale de la plateforme en un coup d\'œil.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            OverviewStatsWidget::class,
            BusinessKpiWidget::class,
            ProspectionFunnelWidget::class,
            MarketOpportunitiesWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
