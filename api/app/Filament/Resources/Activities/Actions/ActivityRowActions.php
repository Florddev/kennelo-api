<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Actions;

use App\Enums\ActivityStatusEnum;
use App\Enums\AdminActionTypeEnum;
use App\Models\Activity;
use App\Services\Admin\Activity\ActivityAdminService;
use App\Services\Admin\Activity\ActivityGoogleService;
use App\Services\Admin\AdminActionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ActivityRowActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            self::approve(),
            self::reject(),
            self::verifyCompany(),
            self::linkGoogle(),
            self::unlinkGoogle(),
        ];
    }

    private static function canModerate(): bool
    {
        return auth()->user()?->can('moderate', Activity::class) ?? false;
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approuver')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Activity $record): bool => self::canModerate() && $record->status !== ActivityStatusEnum::APPROVED)
            ->action(function (Activity $record): void {
                $activity = app(ActivityAdminService::class)->approve($record, auth()->user());
                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::APPROVE_ACTIVITY, [
                    'activity_id' => $activity->id,
                    'activity_name' => $activity->name,
                ]);

                Notification::make()
                    ->title('Professionnel validé')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Refuser')
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->visible(fn (Activity $record): bool => self::canModerate() && $record->status !== ActivityStatusEnum::REJECTED)
            ->schema([
                Textarea::make('reason')
                    ->label('Raison du refus')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(function (Activity $record, array $data): void {
                $activity = app(ActivityAdminService::class)->reject($record, auth()->user(), $data);
                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::REJECT_ACTIVITY, [
                    'activity_id' => $activity->id,
                    'reason' => $data['reason'],
                ]);

                Notification::make()
                    ->title('Professionnel refusé')
                    ->success()
                    ->send();
            });
    }

    public static function verifyCompany(): Action
    {
        return Action::make('verifyCompany')
            ->label('Vérifier l\'entreprise (SIRET)')
            ->icon(Heroicon::ShieldCheck)
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription('Vérifie l\'entreprise via son SIRET/SIREN et met à jour les informations légales.')
            ->visible(fn (): bool => self::canModerate())
            ->action(function (Activity $record): void {
                $activity = app(ActivityAdminService::class)->verifyCompany($record);
                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::UPDATE_ACTIVITY, [
                    'activity_id' => $activity->id,
                    'verify_company' => true,
                ]);

                Notification::make()
                    ->title($activity->company_verified_at !== null ? 'Entreprise vérifiée' : 'Aucune correspondance trouvée')
                    ->color($activity->company_verified_at !== null ? 'success' : 'warning')
                    ->send();
            });
    }

    public static function linkGoogle(): Action
    {
        return Action::make('linkGoogle')
            ->label('Lier à Google')
            ->icon(Heroicon::MapPin)
            ->color('gray')
            ->visible(fn (Activity $record): bool => self::canModerate() && $record->google_place_id === null)
            ->fillForm(function (Activity $record): array {
                $candidate = app(ActivityGoogleService::class)->searchGoogle($record);

                return [
                    'google_place_id' => $candidate['google_place_id'] ?? null,
                    'google_rating' => $candidate['google_rating'] ?? null,
                    'google_reviews_count' => $candidate['google_reviews_count'] ?? null,
                    'google_maps_url' => $candidate['google_maps_url'] ?? null,
                ];
            })
            ->schema([
                TextInput::make('google_place_id')
                    ->label('Google Place ID')
                    ->required()
                    ->maxLength(255),
                TextInput::make('google_rating')
                    ->label('Note Google')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5),
                TextInput::make('google_reviews_count')
                    ->label('Nombre d\'avis')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('google_maps_url')
                    ->label('URL Google Maps')
                    ->url()
                    ->maxLength(255),
            ])
            ->action(function (Activity $record, array $data): void {
                $activity = app(ActivityGoogleService::class)->linkGoogle($record, $data);
                app(AdminActionService::class)->log(auth()->user(), null, AdminActionTypeEnum::UPDATE_ACTIVITY, [
                    'activity_id' => $activity->id,
                    'google_place_id' => $activity->google_place_id,
                ]);

                Notification::make()
                    ->title('Activité liée à Google')
                    ->success()
                    ->send();
            });
    }

    public static function unlinkGoogle(): Action
    {
        return Action::make('unlinkGoogle')
            ->label('Délier de Google')
            ->icon(Heroicon::MapPin)
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Activity $record): bool => self::canModerate() && $record->google_place_id !== null)
            ->action(function (Activity $record): void {
                app(ActivityGoogleService::class)->unlinkGoogle($record);

                Notification::make()
                    ->title('Activité déliée de Google')
                    ->success()
                    ->send();
            });
    }
}
