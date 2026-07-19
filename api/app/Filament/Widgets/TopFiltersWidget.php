<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\Widget;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class TopFiltersWidget extends Widget
{
    use HasSkeleton;

    protected string $view = 'filament.widgets.meter-list';

    protected int|string|array $columnSpan = 1;

    protected function skeletonData(): array
    {
        return ['rows' => 5, 'variant' => 'meter'];
    }

    protected function skeletonHeading(): ?string
    {
        return 'Filtres les plus utilisés';
    }

    protected function skeletonDescription(): ?string
    {
        return 'Comportement de recherche';
    }

    private const LABELS = [
        'sort' => 'Tri',
        'min_rating' => 'Note minimale',
        'max_price' => 'Prix maximum',
        'radius' => 'Rayon',
        'host_type' => 'Type d\'hôte',
        'date_from' => 'Date de début',
        'date_to' => 'Date de fin',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $topFilters = app(StatsService::class)->searches()['top_filters'];
        $max = max(array_map(fn (array $f): int => (int) $f['total'], $topFilters) ?: [0]);
        $max = max($max, 1);

        $rows = array_map(fn (array $f): array => [
            'label' => self::LABELS[$f['filter']] ?? $f['filter'],
            'total' => (int) $f['total'],
            'width' => ((int) $f['total'] / $max) * 100,
        ], $topFilters);

        return [
            'heading' => 'Filtres les plus utilisés',
            'description' => 'Comportement de recherche',
            'barColor' => 'k-fill-primary',
            'rows' => $rows,
            'empty' => 'Aucun filtre utilisé pour l\'instant.',
            'isEmpty' => $rows === [],
        ];
    }
}
