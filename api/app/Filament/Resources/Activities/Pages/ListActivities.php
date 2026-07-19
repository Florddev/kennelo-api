<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Pages;

use App\Enums\ActivityStatusEnum;
use App\Filament\Resources\Activities\ActivityResource;
use App\Models\Activity;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'pending' => Tab::make('En attente')
                ->badge(Activity::query()->where('status', ActivityStatusEnum::PENDING->value)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ActivityStatusEnum::PENDING->value)),
            'approved' => Tab::make('Validés')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ActivityStatusEnum::APPROVED->value)),
            'rejected' => Tab::make('Refusés')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ActivityStatusEnum::REJECTED->value)),
            'crm' => Tab::make('CRM (à lier)')
                ->badge(Activity::query()
                    ->where('status', ActivityStatusEnum::APPROVED->value)
                    ->whereNull('google_place_id')
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', ActivityStatusEnum::APPROVED->value)
                    ->whereNull('google_place_id')),
        ];
    }
}
