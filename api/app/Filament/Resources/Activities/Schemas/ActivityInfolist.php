<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Schemas;

use App\Enums\ActivityStatusEnum;
use App\Models\Activity;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
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
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (ActivityStatusEnum $state): string => match ($state) {
                                ActivityStatusEnum::PENDING => 'En attente',
                                ActivityStatusEnum::APPROVED => 'Validé',
                                ActivityStatusEnum::REJECTED => 'Refusé',
                            })
                            ->color(fn (ActivityStatusEnum $state): string => match ($state) {
                                ActivityStatusEnum::PENDING => 'warning',
                                ActivityStatusEnum::APPROVED => 'success',
                                ActivityStatusEnum::REJECTED => 'danger',
                            }),
                        TextEntry::make('manager.full_name')
                            ->label('Gérant')
                            ->getStateUsing(fn (Activity $record): string => $record->manager !== null
                                ? trim($record->manager->first_name.' '.$record->manager->last_name)
                                : '—'),
                        TextEntry::make('address.city')
                            ->label('Ville')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->placeholder('—'),
                        TextEntry::make('website')
                            ->label('Site web')
                            ->placeholder('—')
                            ->url(fn (Activity $record): ?string => $record->website)
                            ->openUrlInNewTab(),
                    ]),
                Section::make('Entreprise')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('siret')
                            ->label('SIRET')
                            ->fontFamily('mono')
                            ->placeholder('—'),
                        TextEntry::make('siren')
                            ->label('SIREN')
                            ->fontFamily('mono')
                            ->placeholder('—'),
                        TextEntry::make('ape_code')
                            ->label('Code APE')
                            ->placeholder('—'),
                        TextEntry::make('company_verified_at')
                            ->label('Entreprise vérifiée le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non vérifiée'),
                    ]),
                Section::make('Google')
                    ->columns(2)
                    ->visible(fn (Activity $record): bool => $record->google_place_id !== null)
                    ->schema([
                        TextEntry::make('google_rating')
                            ->label('Note Google')
                            ->placeholder('—'),
                        TextEntry::make('google_reviews_count')
                            ->label('Nombre d\'avis')
                            ->placeholder('—'),
                        TextEntry::make('google_maps_url')
                            ->label('Google Maps')
                            ->url(fn (Activity $record): ?string => $record->google_maps_url)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                    ]),
                Section::make('Modération')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('rejection_reason')
                            ->label('Raison du refus')
                            ->visible(fn (Activity $record): bool => $record->status === ActivityStatusEnum::REJECTED)
                            ->placeholder('—'),
                        TextEntry::make('reviewedBy.full_name')
                            ->label('Modéré par')
                            ->getStateUsing(fn (Activity $record): ?string => $record->reviewedBy !== null
                                ? trim($record->reviewedBy->first_name.' '.$record->reviewedBy->last_name)
                                : null)
                            ->placeholder('—'),
                        TextEntry::make('reviewed_at')
                            ->label('Modéré le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Inscription')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
