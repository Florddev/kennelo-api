<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Setting\SettingService;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Settings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $title = 'Paramètres';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function mount(): void
    {
        $stored = app(SettingService::class)->all();

        $this->form->fill([
            'user_service_fee_rate' => (float) $stored['user_service_fee_rate'] * 100,
            'host_commission_rate' => (float) $stored['host_commission_rate'] * 100,
            'acceptance_window_hours' => (int) $stored['acceptance_window_hours'],
            'payout_delay_hours' => (int) $stored['payout_delay_hours'],
            'reminder_after_hours' => (int) $stored['reminder_after_hours'],
            'currency' => (string) $stored['currency'],
            'tier3_enabled' => (bool) $stored['tier3_enabled'],
            'soft_disable_activities' => (bool) $stored['soft_disable_activities'],
            'soft_disable_cycles' => (bool) $stored['soft_disable_cycles'],
            'soft_disable_photos' => (bool) $stored['soft_disable_photos'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Frais & commissions')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user_service_fee_rate')
                            ->label('Frais de service utilisateur (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        TextInput::make('host_commission_rate')
                            ->label('Commission hôte (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                    ]),
                Section::make('Réservations')
                    ->columns(3)
                    ->schema([
                        TextInput::make('acceptance_window_hours')
                            ->label('Fenêtre d\'acceptation (heures)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('payout_delay_hours')
                            ->label('Délai de versement (heures)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('reminder_after_hours')
                            ->label('Relance après (heures)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ]),
                Section::make('Stripe')
                    ->schema([
                        TextInput::make('currency')
                            ->label('Devise')
                            ->required()
                            ->length(3),
                    ]),
                Section::make('Notifications')
                    ->schema([
                        Toggle::make('tier3_enabled')
                            ->label('Notifications tier 3 activées'),
                    ]),
                Section::make('Rétrogradation (soft-disable)')
                    ->columns(3)
                    ->schema([
                        Toggle::make('soft_disable_activities')
                            ->label('Désactiver les activités'),
                        Toggle::make('soft_disable_cycles')
                            ->label('Désactiver les cycles'),
                        Toggle::make('soft_disable_photos')
                            ->label('Désactiver les photos'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(SettingService::class)->update([
            'user_service_fee_rate' => (string) round(((float) $data['user_service_fee_rate']) / 100, 4),
            'host_commission_rate' => (string) round(((float) $data['host_commission_rate']) / 100, 4),
            'acceptance_window_hours' => (int) $data['acceptance_window_hours'],
            'payout_delay_hours' => (int) $data['payout_delay_hours'],
            'reminder_after_hours' => (int) $data['reminder_after_hours'],
            'currency' => (string) $data['currency'],
            'tier3_enabled' => (bool) $data['tier3_enabled'],
            'soft_disable_activities' => (bool) $data['soft_disable_activities'],
            'soft_disable_cycles' => (bool) $data['soft_disable_cycles'],
            'soft_disable_photos' => (bool) $data['soft_disable_photos'],
        ]);

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }
}
