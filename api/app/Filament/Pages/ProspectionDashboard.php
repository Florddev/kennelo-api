<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ProspectionActivitiesWidget;
use App\Filament\Widgets\ProspectionProspectsWidget;
use App\Filament\Widgets\TeamPerformanceWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProspectionDashboard extends BaseDashboard
{
    protected static string $routePath = 'prospection-stats';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Prospection';

    protected static ?string $title = 'Prospection';

    protected static ?int $navigationSort = 6;

    public function getSubheading(): ?string
    {
        return 'Suivi commercial : validation des pros et conversion des prospects.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            ProspectionActivitiesWidget::class,
            ProspectionProspectsWidget::class,
            TeamPerformanceWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
