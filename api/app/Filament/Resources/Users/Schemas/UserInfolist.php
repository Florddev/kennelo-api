<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nom complet')
                            ->getStateUsing(fn (User $record): string => trim($record->first_name.' '.$record->last_name)),
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->placeholder('—'),
                        TextEntry::make('locale')
                            ->label('Langue')
                            ->placeholder('—'),
                    ]),
                Section::make('Statut')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (UserStatusEnum $state): string => match ($state) {
                                UserStatusEnum::ACTIVE => 'Actif',
                                UserStatusEnum::INACTIVE => 'Inactif',
                                UserStatusEnum::BANNED => 'Banni',
                            })
                            ->color(fn (UserStatusEnum $state): string => match ($state) {
                                UserStatusEnum::ACTIVE => 'success',
                                UserStatusEnum::INACTIVE => 'gray',
                                UserStatusEnum::BANNED => 'danger',
                            }),
                        TextEntry::make('roles.name')
                            ->label('Rôles')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'admin' => 'Administrateur',
                                'manager' => 'Gestionnaire',
                                'user' => 'Utilisateur',
                                default => $state,
                            }),
                        TextEntry::make('is_id_verified')
                            ->label('Identité vérifiée')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('email_verified_at')
                            ->label('E-mail vérifié le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non vérifié'),
                    ]),
                Section::make('Bannissement')
                    ->columns(2)
                    ->visible(fn (User $record): bool => $record->isBanned())
                    ->schema([
                        TextEntry::make('ban_reason')
                            ->label('Raison'),
                        TextEntry::make('banned_at')
                            ->label('Banni le')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('banned_until')
                            ->label('Jusqu\'au')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Permanent'),
                    ]),
                Section::make('Métadonnées')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Inscription')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('last_seen_at')
                            ->label('Dernière activité')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
