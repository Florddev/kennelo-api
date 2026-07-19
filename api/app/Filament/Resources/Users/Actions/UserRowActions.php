<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Enums\AdminActionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\Admin\AdminActionService;
use App\Services\Admin\User\AdminUserService;
use App\Services\Admin\User\ExportService;
use App\Services\Admin\User\ImpersonationService;
use App\Services\User\Exceptions\UserHasActiveBookingsException;
use App\Services\User\UserService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserRowActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            self::ban(),
            self::unban(),
            self::toggleStatus(),
            self::verifyEmail(),
            self::forcePasswordReset(),
            self::impersonate(),
            self::delete(),
        ];
    }

    public static function ban(): Action
    {
        return Action::make('ban')
            ->label('Bannir')
            ->icon(Heroicon::NoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => auth()->user()->can('ban', $record) && ! $record->isBanned())
            ->schema([
                Textarea::make('reason')
                    ->label('Raison du bannissement')
                    ->required()
                    ->maxLength(500),
                DateTimePicker::make('banned_until')
                    ->label('Banni jusqu\'au (optionnel)')
                    ->helperText('Laisser vide pour un bannissement permanent.')
                    ->after('now'),
            ])
            ->action(function (User $record, array $data): void {
                app(AdminUserService::class)->ban($record, auth()->user(), $data);
                app(AdminActionService::class)->log(auth()->user(), $record, AdminActionTypeEnum::BAN, $data);

                Notification::make()
                    ->title('Utilisateur banni')
                    ->success()
                    ->send();
            });
    }

    public static function unban(): Action
    {
        return Action::make('unban')
            ->label('Débannir')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => auth()->user()->can('unban', $record) && $record->isBanned())
            ->action(function (User $record): void {
                app(AdminUserService::class)->unban($record);
                app(AdminActionService::class)->log(auth()->user(), $record, AdminActionTypeEnum::UNBAN);

                Notification::make()
                    ->title('Utilisateur débanni')
                    ->success()
                    ->send();
            });
    }

    public static function toggleStatus(): Action
    {
        return Action::make('toggleStatus')
            ->label(fn (User $record): string => $record->status === UserStatusEnum::ACTIVE ? 'Désactiver' : 'Activer')
            ->icon(fn (User $record): Heroicon => $record->status === UserStatusEnum::ACTIVE ? Heroicon::PauseCircle : Heroicon::PlayCircle)
            ->color(fn (User $record): string => $record->status === UserStatusEnum::ACTIVE ? 'gray' : 'success')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => auth()->user()->can('updateStatus', $record) && ! $record->isBanned())
            ->action(function (User $record): void {
                $next = $record->status === UserStatusEnum::ACTIVE
                    ? UserStatusEnum::INACTIVE->value
                    : UserStatusEnum::ACTIVE->value;

                app(UserService::class)->updateStatus($record, ['status' => $next]);
                app(AdminActionService::class)->log(auth()->user(), $record, AdminActionTypeEnum::UPDATE_STATUS, ['status' => $next]);

                Notification::make()
                    ->title('Statut mis à jour')
                    ->success()
                    ->send();
            });
    }

    public static function verifyEmail(): Action
    {
        return Action::make('verifyEmail')
            ->label('Vérifier l\'e-mail')
            ->icon(Heroicon::Envelope)
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => auth()->user()->can('verifyEmail', $record) && ! $record->hasVerifiedEmail())
            ->action(function (User $record): void {
                app(AdminUserService::class)->verifyEmail($record);
                app(AdminActionService::class)->log(auth()->user(), $record, AdminActionTypeEnum::VERIFY_EMAIL);

                Notification::make()
                    ->title('E-mail vérifié')
                    ->success()
                    ->send();
            });
    }

    public static function forcePasswordReset(): Action
    {
        return Action::make('forcePasswordReset')
            ->label('Réinitialiser le mot de passe')
            ->icon(Heroicon::Key)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Un lien de réinitialisation sera envoyé à l\'utilisateur.')
            ->visible(fn (User $record): bool => auth()->user()->can('forcePasswordReset', $record))
            ->action(function (User $record): void {
                app(AdminUserService::class)->forcePasswordReset($record);
                app(AdminActionService::class)->log(auth()->user(), $record, AdminActionTypeEnum::FORCE_PASSWORD_RESET);

                Notification::make()
                    ->title('Lien de réinitialisation envoyé')
                    ->success()
                    ->send();
            });
    }

    public static function impersonate(): Action
    {
        return Action::make('impersonate')
            ->label('Impersonation')
            ->icon(Heroicon::UserCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Génère un jeton d\'impersonation temporaire (15 min) utilisable dans l\'application Kennelo.')
            ->visible(fn (User $record): bool => auth()->user()->can('impersonate', $record))
            ->action(function (User $record): void {
                $result = app(ImpersonationService::class)->start($record, auth()->user());

                Notification::make()
                    ->title('Jeton d\'impersonation généré')
                    ->body('Valide '.((int) $result['expires_in'] / 60).' minutes — à utiliser comme Bearer dans l\'application Kennelo :'
                        .PHP_EOL.PHP_EOL.'`'.$result['token'].'`')
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    public static function delete(): Action
    {
        return Action::make('delete')
            ->label('Supprimer')
            ->icon(Heroicon::Trash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Cette action supprime le compte (soft delete). Impossible si l\'utilisateur a des réservations actives.')
            ->visible(fn (User $record): bool => auth()->user()->can('destroy', $record))
            ->action(function (User $record): void {
                try {
                    app(UserService::class)->deleteAccount($record);
                    app(AdminActionService::class)->log(auth()->user(), $record, AdminActionTypeEnum::DELETE);

                    Notification::make()
                        ->title('Utilisateur supprimé')
                        ->success()
                        ->send();
                } catch (UserHasActiveBookingsException $e) {
                    Notification::make()
                        ->title('Suppression impossible')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * @return array<int, BulkActionGroup>
     */
    public static function bulk(): array
    {
        return [
            BulkActionGroup::make([
                self::bulkStatus(),
                self::bulkRoles(),
            ]),
        ];
    }

    public static function bulkStatus(): BulkAction
    {
        return BulkAction::make('bulkStatus')
            ->label('Changer le statut')
            ->icon(Heroicon::AdjustmentsHorizontal)
            ->schema([
                Select::make('status')
                    ->label('Nouveau statut')
                    ->required()
                    ->options([
                        UserStatusEnum::ACTIVE->value => 'Actif',
                        UserStatusEnum::INACTIVE->value => 'Inactif',
                    ]),
            ])
            ->action(function (Collection $records, array $data): void {
                $count = app(AdminUserService::class)->bulkStatus(
                    $records->pluck('id')->all(),
                    (int) $data['status'],
                    auth()->id(),
                );

                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::BULK_STATUS, [
                    'status' => (int) $data['status'],
                    'count' => $count,
                ]);

                Notification::make()
                    ->title("{$count} utilisateur(s) mis à jour")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function bulkRoles(): BulkAction
    {
        return BulkAction::make('bulkRoles')
            ->label('Gérer les rôles')
            ->icon(Heroicon::ShieldCheck)
            ->schema([
                Select::make('action')
                    ->label('Action')
                    ->required()
                    ->options([
                        'assign' => 'Assigner',
                        'remove' => 'Retirer',
                    ]),
                Select::make('roles')
                    ->label('Rôles')
                    ->required()
                    ->multiple()
                    ->options([
                        'manager' => 'Gestionnaire',
                        'user' => 'Utilisateur',
                    ]),
            ])
            ->action(function (Collection $records, array $data): void {
                $count = app(AdminUserService::class)->bulkRoles(
                    $records->pluck('id')->all(),
                    $data['action'],
                    $data['roles'],
                );

                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::BULK_ROLES, [
                    'action' => $data['action'],
                    'roles' => $data['roles'],
                    'count' => $count,
                ]);

                Notification::make()
                    ->title("{$count} utilisateur(s) mis à jour")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function export(): Action
    {
        return Action::make('export')
            ->label('Exporter (CSV)')
            ->icon(Heroicon::ArrowDownTray)
            ->color('gray')
            ->action(function ($livewire): StreamedResponse {
                $filters = collect([
                    'search' => $livewire->getTableSearch(),
                    'role' => data_get($livewire->tableFilters, 'role.value'),
                ])->filter(fn ($value) => filled($value))->all();

                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::EXPORT, $filters);

                return app(ExportService::class)->streamCsv($filters);
            });
    }
}
