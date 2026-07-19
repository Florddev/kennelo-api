<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Actions;

use App\Models\Prospect;
use App\Services\Admin\Prospect\ProspectService;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ImportProspectsAction
{
    public static function make(): Action
    {
        return Action::make('import')
            ->label('Importer')
            ->icon(Heroicon::ArrowDownTray)
            ->color('primary')
            ->visible(fn (): bool => auth()->user()?->can('import', Prospect::class) ?? false)
            ->schema([
                TextInput::make('location')
                    ->label('Localisation')
                    ->placeholder('Paris, Lyon, département…')
                    ->required()
                    ->maxLength(255),
                TagsInput::make('search_terms')
                    ->label('Termes de recherche (optionnel)')
                    ->placeholder('pension canine, chenil…')
                    ->helperText('Laisser vide pour utiliser les termes par défaut.'),
                TextInput::make('max_results')
                    ->label('Résultats maximum')
                    ->numeric()
                    ->default(20)
                    ->minValue(1)
                    ->maxValue(500),
            ])
            ->action(function (array $data): void {
                app(ProspectService::class)->startImport($data, auth()->user());

                Notification::make()
                    ->title('Import lancé')
                    ->body('L\'import s\'exécute en arrière-plan. Les prospects apparaîtront une fois terminé.')
                    ->success()
                    ->send();
            });
    }
}
