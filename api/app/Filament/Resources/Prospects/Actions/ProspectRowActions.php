<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Actions;

use App\Enums\ProspectStatusEnum;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Admin\Prospect\ProspectService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ProspectRowActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            self::updateStatus(),
            self::assign(),
            self::reconcile(),
            self::delete(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            ProspectStatusEnum::NON_CONTACTE->value => 'Non contacté',
            ProspectStatusEnum::CONTACTE->value => 'Contacté',
            ProspectStatusEnum::RELANCE->value => 'Relancé',
            ProspectStatusEnum::INSCRIT->value => 'Inscrit',
            ProspectStatusEnum::REFUSE->value => 'Refusé',
        ];
    }

    public static function updateStatus(): Action
    {
        return Action::make('updateStatus')
            ->label('Changer le statut')
            ->icon(Heroicon::AdjustmentsHorizontal)
            ->color('info')
            ->fillForm(fn (Prospect $record): array => ['status' => $record->status->value])
            ->schema([
                Select::make('status')
                    ->label('Statut')
                    ->required()
                    ->options(self::statusOptions()),
            ])
            ->action(function (Prospect $record, array $data): void {
                app(ProspectService::class)->updateStatus($record, $data);

                Notification::make()
                    ->title('Statut mis à jour')
                    ->success()
                    ->send();
            });
    }

    public static function assign(): Action
    {
        return Action::make('assign')
            ->label('Assigner')
            ->icon(Heroicon::UserPlus)
            ->color('gray')
            ->fillForm(fn (Prospect $record): array => ['assigned_to' => $record->assigned_to])
            ->schema([
                Select::make('assigned_to')
                    ->label('Membre de l\'équipe')
                    ->placeholder('Non assigné')
                    ->options(fn (): array => User::withInactive()
                        ->role('admin')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->id => trim($user->first_name.' '.$user->last_name),
                        ])
                        ->all()),
            ])
            ->action(function (Prospect $record, array $data): void {
                app(ProspectService::class)->assign($record, $data);

                Notification::make()
                    ->title('Prospect assigné')
                    ->success()
                    ->send();
            });
    }

    public static function reconcile(): Action
    {
        return Action::make('reconcile')
            ->label('Rapprocher (SIRET)')
            ->icon(Heroicon::LinkSlash)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Tente de rapprocher ce prospect d\'une activité Kennelo existante via son SIRET.')
            ->action(function (Prospect $record): void {
                $prospect = app(ProspectService::class)->reconcile($record);

                Notification::make()
                    ->title($prospect->kennelo_activity_id !== null ? 'Prospect rapproché' : 'Aucune correspondance trouvée')
                    ->color($prospect->kennelo_activity_id !== null ? 'success' : 'warning')
                    ->send();
            });
    }

    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Supprimer')
            ->using(function (Prospect $record): void {
                app(ProspectService::class)->delete($record);
            });
    }
}
