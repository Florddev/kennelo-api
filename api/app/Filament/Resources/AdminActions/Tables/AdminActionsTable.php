<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActions\Tables;

use App\Enums\AdminActionTypeEnum;
use App\Models\AdminAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdminActionsTable
{
    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            AdminActionTypeEnum::BAN->value => 'Bannissement',
            AdminActionTypeEnum::UNBAN->value => 'Débannissement',
            AdminActionTypeEnum::FORCE_PASSWORD_RESET->value => 'Réinit. mot de passe',
            AdminActionTypeEnum::VERIFY_EMAIL->value => 'Vérification e-mail',
            AdminActionTypeEnum::RESEND_VERIFICATION->value => 'Renvoi de vérification',
            AdminActionTypeEnum::UPDATE_STATUS->value => 'Changement de statut',
            AdminActionTypeEnum::ASSIGN_ROLES->value => 'Attribution de rôles',
            AdminActionTypeEnum::REMOVE_ROLE->value => 'Retrait de rôle',
            AdminActionTypeEnum::REVIEW_IDENTITY->value => 'Revue d\'identité',
            AdminActionTypeEnum::DELETE->value => 'Suppression',
            AdminActionTypeEnum::IMPERSONATE_START->value => 'Début impersonation',
            AdminActionTypeEnum::IMPERSONATE_STOP->value => 'Fin impersonation',
            AdminActionTypeEnum::BULK_STATUS->value => 'Statut (en masse)',
            AdminActionTypeEnum::BULK_ROLES->value => 'Rôles (en masse)',
            AdminActionTypeEnum::EXPORT->value => 'Export',
            AdminActionTypeEnum::APPROVE_ACTIVITY->value => 'Validation activité',
            AdminActionTypeEnum::REJECT_ACTIVITY->value => 'Refus activité',
            AdminActionTypeEnum::UPDATE_ACTIVITY->value => 'Mise à jour activité',
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'admin' => fn ($q) => $q->withoutGlobalScope('active')->withTrashed(),
                'target' => fn ($q) => $q->withoutGlobalScope('active')->withTrashed(),
            ]))
            ->columns([
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (AdminActionTypeEnum $state): string => self::actionLabels()[$state->value] ?? $state->value),
                TextColumn::make('admin.email')
                    ->label('Administrateur')
                    ->getStateUsing(fn (AdminAction $record): string => $record->admin !== null
                        ? trim($record->admin->first_name.' '.$record->admin->last_name)
                        : '—'),
                TextColumn::make('target.email')
                    ->label('Cible')
                    ->getStateUsing(fn (AdminAction $record): string => $record->target !== null
                        ? trim($record->target->first_name.' '.$record->target->last_name)
                        : '—'),
                TextColumn::make('metadata')
                    ->label('Détails')
                    ->formatStateUsing(fn (AdminAction $record): string => is_array($record->metadata) && $record->metadata !== []
                        ? collect($record->metadata)->map(fn ($value, $key): string => "{$key}:".(is_scalar($value) ? (string) $value : '…'))->implode(' · ')
                        : '—')
                    ->wrap()
                    ->limit(60),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(self::actionLabels()),
            ]);
    }
}
