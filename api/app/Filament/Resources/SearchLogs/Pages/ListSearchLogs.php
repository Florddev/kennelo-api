<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchLogs\Pages;

use App\Filament\Resources\SearchLogs\SearchLogResource;
use Filament\Resources\Pages\ListRecords;

class ListSearchLogs extends ListRecords
{
    protected static string $resource = SearchLogResource::class;
}
