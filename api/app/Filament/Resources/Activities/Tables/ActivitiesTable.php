<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Tables;

use App\Enums\ActivityStatusEnum;
use App\Filament\Resources\Activities\Actions\ActivityRowActions;
use App\Models\Activity;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn ($query) => $query->with(['address', 'manager']))
            ->columns([
                TextColumn::make('name')
                    ->label('Professionnel')
                    ->description(fn (Activity $record): string => trim(implode(' · ', array_filter([
                        $record->manager !== null ? trim($record->manager->first_name.' '.$record->manager->last_name) : null,
                        $record->address?->city,
                    ]))))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siret')
                    ->label('SIRET')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->icon(fn (Activity $record): ?string => $record->company_verified_at !== null ? 'heroicon-m-check-badge' : null)
                    ->iconColor('success'),
                TextColumn::make('status')
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
                TextColumn::make('type_label')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn (Activity $record): string => $record->siret !== null ? 'Pro' : 'Particulier')
                    ->color(fn (Activity $record): string => $record->siret !== null ? 'info' : 'gray'),
                IconColumn::make('google_place_id')
                    ->label('Google')
                    ->boolean()
                    ->getStateUsing(fn (Activity $record): bool => $record->google_place_id !== null),
                TextColumn::make('created_at')
                    ->label('Inscription')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        ActivityStatusEnum::PENDING->value => 'En attente',
                        ActivityStatusEnum::APPROVED->value => 'Validé',
                        ActivityStatusEnum::REJECTED->value => 'Refusé',
                    ]),
                TernaryFilter::make('professional')
                    ->label('Type')
                    ->placeholder('Tous')
                    ->trueLabel('Professionnels (avec SIRET)')
                    ->falseLabel('Particuliers')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('siret'),
                        false: fn ($query) => $query->whereNull('siret'),
                        blank: fn ($query) => $query,
                    ),
                TernaryFilter::make('google')
                    ->label('Liaison Google')
                    ->placeholder('Tous')
                    ->trueLabel('Liés à Google')
                    ->falseLabel('Non liés')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('google_place_id'),
                        false: fn ($query) => $query->whereNull('google_place_id'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ...ActivityRowActions::make(),
                ]),
            ]);
    }
}
