<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReviewReports;

use App\Enums\ReviewReportStatusEnum;
use App\Filament\Resources\ReviewReports\Pages\ListReviewReports;
use App\Filament\Resources\ReviewReports\Tables\ReviewReportsTable;
use App\Models\ReviewReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class ReviewReportResource extends Resource
{
    protected static ?string $model = ReviewReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $navigationLabel = 'Modération';

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Cache::remember(
            'filament:badge:review-reports-pending',
            60,
            fn (): int => ReviewReport::query()
                ->where('status', ReviewReportStatusEnum::PENDING->value)
                ->count(),
        );

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ReviewReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviewReports::route('/'),
        ];
    }
}
