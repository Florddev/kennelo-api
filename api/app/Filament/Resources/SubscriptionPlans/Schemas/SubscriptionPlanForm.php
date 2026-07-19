<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionPlans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Général')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('price_monthly')
                            ->label('Prix mensuel')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix('€'),
                        TextInput::make('commission_rate')
                            ->label('Commission (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        Toggle::make('is_active')
                            ->label('Plan actif'),
                        TextInput::make('description')
                            ->label('Description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
                Section::make('Limites')
                    ->columns(3)
                    ->schema([
                        TextInput::make('limits.max_activities')
                            ->label('Activités max')
                            ->numeric()
                            ->minValue(-1)
                            ->helperText('-1 = illimité'),
                        TextInput::make('limits.max_cycles_per_activity')
                            ->label('Cycles / activité max')
                            ->numeric()
                            ->minValue(-1)
                            ->helperText('-1 = illimité'),
                        TextInput::make('limits.max_photos')
                            ->label('Photos max')
                            ->numeric()
                            ->minValue(-1)
                            ->helperText('-1 = illimité'),
                    ]),
            ]);
    }
}
