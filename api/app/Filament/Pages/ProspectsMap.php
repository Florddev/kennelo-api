<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ProspectStatusEnum;
use App\Services\Admin\Prospect\ProspectService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ProspectsMap extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.prospects-map';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Prospection';

    protected static ?string $navigationLabel = 'Carte';

    protected static ?string $title = 'Carte des pensions';

    protected static ?int $navigationSort = 2;

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
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('status')
                    ->label('Statut')
                    ->placeholder('Tous les statuts')
                    ->options(self::statusOptions())
                    ->live()
                    ->afterStateUpdated(fn () => $this->pushGeoJson()),
                Select::make('registered')
                    ->label('Inscription Kennelo')
                    ->placeholder('Tous')
                    ->options([
                        'registered' => 'Inscrits',
                        'not_registered' => 'Non inscrits',
                    ])
                    ->live()
                    ->afterStateUpdated(fn () => $this->pushGeoJson()),
            ])
            ->statePath('data');
    }

    public function pushGeoJson(): void
    {
        $this->dispatch('prospects-updated', geojson: $this->getGeoJson());
    }

    /**
     * @return array<string, mixed>
     */
    public function getGeoJson(): array
    {
        $filters = [];

        if (filled($this->data['status'] ?? null)) {
            $filters['status'] = $this->data['status'];
        }

        if (($this->data['registered'] ?? null) === 'registered') {
            $filters['registered'] = true;
        } elseif (($this->data['registered'] ?? null) === 'not_registered') {
            $filters['registered'] = false;
        }

        return app(ProspectService::class)->mapGeoJson($filters);
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
}
