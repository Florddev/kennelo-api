<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\RelationManagers;

use App\Enums\ProspectContactTypeEnum;
use App\Models\ProspectContact;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Contacts';

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return [
            ProspectContactTypeEnum::CALL->value => 'Appel',
            ProspectContactTypeEnum::EMAIL->value => 'E-mail',
            ProspectContactTypeEnum::SMS->value => 'SMS',
            ProspectContactTypeEnum::MEETING->value => 'Rendez-vous',
            ProspectContactTypeEnum::OTHER->value => 'Autre',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Type')
                    ->required()
                    ->options(self::typeOptions()),
                DateTimePicker::make('contacted_at')
                    ->label('Contacté le')
                    ->default(now()),
                TextInput::make('outcome')
                    ->label('Résultat')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('outcome')
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ProspectContactTypeEnum $state): string => self::typeOptions()[$state->value] ?? $state->value),
                TextColumn::make('outcome')
                    ->label('Résultat')
                    ->placeholder('—'),
                TextColumn::make('author.full_name')
                    ->label('Auteur')
                    ->getStateUsing(fn (ProspectContact $record): string => $record->author !== null
                        ? trim($record->author->first_name.' '.$record->author->last_name)
                        : '—'),
                TextColumn::make('contacted_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('contacted_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un contact')
                    ->mutateDataUsing(function (array $data): array {
                        $data['author_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
