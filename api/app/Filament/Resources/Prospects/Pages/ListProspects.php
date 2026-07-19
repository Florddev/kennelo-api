<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\Actions\ImportProspectsAction;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Filament\Resources\Prospects\Widgets\ProspectsStatsOverview;
use Filament\Resources\Pages\ListRecords;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportProspectsAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProspectsStatsOverview::class,
        ];
    }
}
