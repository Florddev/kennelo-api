<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchLogs\Tables;

use App\Models\SearchLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class SearchLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('location')
                    ->label('Localisation')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('department')
                    ->label('Département')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('filters')
                    ->label('Filtres')
                    ->formatStateUsing(fn (SearchLog $record): string => is_array($record->filters) && $record->filters !== []
                        ? collect($record->filters)->map(fn ($value, $key): string => "{$key}:".(is_scalar($value) ? (string) $value : '…'))->implode(' · ')
                        : '—')
                    ->wrap()
                    ->limit(60),
                TextColumn::make('results_count')
                    ->label('Résultats')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->placeholder('Anonyme'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('department')
                    ->label('Département')
                    ->options(fn (): array => Cache::remember(
                        'filament:search-logs:departments',
                        300,
                        fn (): array => SearchLog::query()
                            ->whereNotNull('department')
                            ->distinct()
                            ->orderBy('department')
                            ->pluck('department', 'department')
                            ->all(),
                    )),
            ]);
    }
}
