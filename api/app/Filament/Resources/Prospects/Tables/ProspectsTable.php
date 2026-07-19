<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Tables;

use App\Enums\ProspectStatusEnum;
use App\Filament\Resources\Prospects\Actions\ProspectRowActions;
use App\Models\Prospect;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class ProspectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Établissement')
                    ->description(fn (Prospect $record): string => trim(implode(' · ', array_filter([
                        trim(($record->address ?? '').' '.($record->postal_code ?? '').' '.($record->city ?? '')),
                        $record->category,
                    ]))))
                    ->searchable(query: fn ($query, string $search) => $query->where(fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")))
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Contact')
                    ->placeholder('—')
                    ->description(fn (Prospect $record): ?string => $record->website)
                    ->url(fn (Prospect $record): ?string => $record->website)
                    ->openUrlInNewTab(),
                TextColumn::make('google_rating')
                    ->label('Note Google')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?float $state, Prospect $record): string => $state !== null
                        ? '★ '.$state.' ('.($record->google_reviews_count ?? 0).')'
                        : '—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ProspectStatusEnum $state): string => match ($state) {
                        ProspectStatusEnum::NON_CONTACTE => 'Non contacté',
                        ProspectStatusEnum::CONTACTE => 'Contacté',
                        ProspectStatusEnum::RELANCE => 'Relancé',
                        ProspectStatusEnum::INSCRIT => 'Inscrit',
                        ProspectStatusEnum::REFUSE => 'Refusé',
                    })
                    ->color(fn (ProspectStatusEnum $state): string => match ($state) {
                        ProspectStatusEnum::NON_CONTACTE => 'gray',
                        ProspectStatusEnum::CONTACTE => 'info',
                        ProspectStatusEnum::RELANCE => 'warning',
                        ProspectStatusEnum::INSCRIT => 'success',
                        ProspectStatusEnum::REFUSE => 'danger',
                    }),
                IconColumn::make('kennelo_activity_id')
                    ->label('Kennelo')
                    ->boolean()
                    ->getStateUsing(fn (Prospect $record): bool => $record->kennelo_activity_id !== null),
                TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ProspectRowActions::statusOptions()),
                SelectFilter::make('department')
                    ->label('Département')
                    ->options(fn (): array => Cache::remember(
                        'filament:prospects:departments',
                        300,
                        fn (): array => Prospect::query()
                            ->whereNotNull('department')
                            ->distinct()
                            ->orderBy('department')
                            ->pluck('department', 'department')
                            ->all(),
                    )),
                TernaryFilter::make('registered')
                    ->label('Inscription Kennelo')
                    ->placeholder('Tous')
                    ->trueLabel('Inscrits')
                    ->falseLabel('Non inscrits')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('kennelo_activity_id'),
                        false: fn ($query) => $query->whereNull('kennelo_activity_id'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ...ProspectRowActions::make(),
                ]),
            ]);
    }
}
