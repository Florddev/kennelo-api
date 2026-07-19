<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActions;

use App\Filament\Resources\AdminActions\Pages\ListAdminActions;
use App\Filament\Resources\AdminActions\Tables\AdminActionsTable;
use App\Models\AdminAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AdminActionResource extends Resource
{
    protected static ?string $model = AdminAction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $navigationLabel = 'Journal';

    protected static ?string $modelLabel = 'action';

    protected static ?string $pluralModelLabel = 'journal d\'activité';

    protected static ?int $navigationSort = 1;

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
        return AdminActionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminActions::route('/'),
        ];
    }
}
