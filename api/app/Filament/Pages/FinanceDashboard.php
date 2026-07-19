<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\FinanceChartWidget;
use App\Filament\Widgets\FinanceStatsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FinanceDashboard extends BaseDashboard
{
    protected static string $routePath = 'finance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Finance';

    protected static ?string $title = 'Finance';

    protected static ?int $navigationSort = 2;

    public function getSubheading(): ?string
    {
        return 'Chiffre d\'affaires, revenu Kennelo et santé économique.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            FinanceStatsWidget::class,
            FinanceChartWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
