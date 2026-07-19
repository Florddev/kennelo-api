<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects;

use App\Filament\Resources\Prospects\Pages\ListProspects;
use App\Filament\Resources\Prospects\Pages\ViewProspect;
use App\Filament\Resources\Prospects\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Prospects\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Prospects\Schemas\ProspectInfolist;
use App\Filament\Resources\Prospects\Tables\ProspectsTable;
use App\Models\Prospect;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Prospection';

    protected static ?string $navigationLabel = 'Prospects';

    protected static ?string $modelLabel = 'prospect';

    protected static ?string $pluralModelLabel = 'prospects';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProspectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProspectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            ContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProspects::route('/'),
            'view' => ViewProspect::route('/{record}'),
        ];
    }
}
