<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\SearchDepartmentsChartWidget;
use App\Filament\Widgets\SearchesChartWidget;
use App\Filament\Widgets\SearchQualityWidget;
use App\Filament\Widgets\TopFiltersWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SearchesDashboard extends BaseDashboard
{
    protected static string $routePath = 'recherches-stats';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Recherches';

    protected static ?string $title = 'Recherches utilisateurs';

    protected static ?int $navigationSort = 5;

    public function getSubheading(): ?string
    {
        return 'Demande des utilisateurs, qualité et comportement de recherche.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            SearchQualityWidget::class,
            TopFiltersWidget::class,
            SearchesChartWidget::class,
            SearchDepartmentsChartWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
