<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingsChartWidget;
use App\Filament\Widgets\BookingsStatsWidget;
use App\Filament\Widgets\BookingStatusBreakdownWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BookingsDashboard extends BaseDashboard
{
    protected static string $routePath = 'reservations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Réservations';

    protected static ?string $title = 'Réservations';

    protected static ?int $navigationSort = 3;

    public function getSubheading(): ?string
    {
        return 'Volume, conversion et statuts des réservations.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getWidgets(): array
    {
        return [
            BookingsStatsWidget::class,
            BookingsChartWidget::class,
            BookingStatusBreakdownWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
