<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchLogs;

use App\Filament\Resources\SearchLogs\Pages\ListSearchLogs;
use App\Filament\Resources\SearchLogs\Tables\SearchLogsTable;
use App\Models\SearchLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SearchLogResource extends Resource
{
    protected static ?string $model = SearchLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Plateforme';

    protected static ?string $navigationLabel = 'Logs de recherche';

    protected static ?string $modelLabel = 'recherche';

    protected static ?string $pluralModelLabel = 'recherches';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return SearchLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSearchLogs::route('/'),
        ];
    }
}
