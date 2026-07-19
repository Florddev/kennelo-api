<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionPlans\Tables;

use App\Models\SubscriptionPlan;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => (string) str($state)->ucfirst())
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'starter' => 'info',
                        'pro' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('price_monthly')
                    ->label('Prix mensuel')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->formatStateUsing(fn (SubscriptionPlan $record): string => round(((float) $record->commission_rate) * 100, 2).' %'),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('price_monthly', 'asc')
            ->recordActions([
                EditAction::make()
                    ->label('Modifier'),
            ]);
    }
}
