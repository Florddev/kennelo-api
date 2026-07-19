<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Schemas;

use App\Enums\ProspectStatusEnum;
use App\Models\Prospect;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProspectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Établissement')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nom'),
                        TextEntry::make('category')
                            ->label('Catégorie')
                            ->placeholder('—'),
                        TextEntry::make('address')
                            ->label('Adresse')
                            ->getStateUsing(fn (Prospect $record): string => trim(($record->address ?? '').' '.($record->postal_code ?? '').' '.($record->city ?? '')))
                            ->placeholder('—'),
                        TextEntry::make('department')
                            ->label('Département')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->placeholder('—'),
                        TextEntry::make('website')
                            ->label('Site web')
                            ->url(fn (Prospect $record): ?string => $record->website)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                    ]),
                Section::make('Google')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('google_rating')
                            ->label('Note Google')
                            ->placeholder('—'),
                        TextEntry::make('google_reviews_count')
                            ->label('Nombre d\'avis')
                            ->placeholder('—'),
                    ]),
                Section::make('Suivi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (ProspectStatusEnum $state): string => match ($state) {
                                ProspectStatusEnum::NON_CONTACTE => 'Non contacté',
                                ProspectStatusEnum::CONTACTE => 'Contacté',
                                ProspectStatusEnum::RELANCE => 'Relancé',
                                ProspectStatusEnum::INSCRIT => 'Inscrit',
                                ProspectStatusEnum::REFUSE => 'Refusé',
                            }),
                        TextEntry::make('assignedTo.full_name')
                            ->label('Assigné à')
                            ->getStateUsing(fn (Prospect $record): ?string => $record->assignedTo !== null
                                ? trim($record->assignedTo->first_name.' '.$record->assignedTo->last_name)
                                : null)
                            ->placeholder('Non assigné'),
                        TextEntry::make('siret')
                            ->label('SIRET')
                            ->fontFamily('mono')
                            ->placeholder('—'),
                        TextEntry::make('kennelo_activity_id')
                            ->label('Activité Kennelo liée')
                            ->formatStateUsing(fn (?string $state): string => $state !== null ? 'Oui' : 'Non')
                            ->badge()
                            ->color(fn (?string $state): string => $state !== null ? 'success' : 'gray'),
                        TextEntry::make('created_at')
                            ->label('Ajouté le')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
