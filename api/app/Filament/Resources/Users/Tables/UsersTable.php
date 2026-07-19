<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserStatusEnum;
use App\Filament\Resources\Users\Actions\UserRowActions;
use App\Models\User;
use App\Services\MediaService;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn ($query) => $query->with(['media', 'roles']))
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (User $record): ?string => $record->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
                        ?: ($record->getFirstMediaUrl(MediaService::COLLECTION_AVATAR) ?: null))
                    ->defaultImageUrl(fn (User $record): string => self::initialsAvatar($record)),
                TextColumn::make('full_name')
                    ->label('Utilisateur')
                    ->getStateUsing(fn (User $record): string => trim($record->first_name.' '.$record->last_name))
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(query: fn ($query, string $search) => $query->where(function ($q) use ($search): void {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    }))
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('first_name', $direction)
                        ->orderBy('last_name', $direction)),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
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
                TextColumn::make('is_id_verified')
                    ->label('Identité')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Vérifiée' : 'Non vérifiée')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrateur',
                        'manager' => 'Gestionnaire',
                        'user' => 'Utilisateur',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'warning',
                        'manager' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Inscription')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'admin' => 'Administrateur',
                        'manager' => 'Gestionnaire',
                        'user' => 'Utilisateur',
                    ])
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']))
                        : $query),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        UserStatusEnum::ACTIVE->value => 'Actif',
                        UserStatusEnum::INACTIVE->value => 'Inactif',
                        UserStatusEnum::BANNED->value => 'Banni',
                    ])
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->where('status', (int) $data['value'])
                        : $query),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ...UserRowActions::make(),
                ]),
            ])
            ->toolbarActions(UserRowActions::bulk())
            ->headerActions([
                UserRowActions::export(),
            ]);
    }

    private static function initialsAvatar(User $record): string
    {
        $initials = (string) str($record->first_name)->substr(0, 1)->upper()
            ->append((string) str($record->last_name)->substr(0, 1)->upper());

        if (blank($initials)) {
            $initials = (string) str($record->email)->substr(0, 1)->upper();
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64">'
            .'<rect width="64" height="64" fill="#059669"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="24" fill="#ffffff">'
            .e($initials)
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
