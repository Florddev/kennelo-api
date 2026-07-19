<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\Widget;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class RatingDistributionWidget extends Widget
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
        return 'Distribution des notes';
    }

    protected function skeletonDescription(): ?string
    {
        return 'Satisfaction';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $distribution = app(StatsService::class)->community()['rating_distribution'];
        $max = max(array_map('intval', $distribution) ?: [0]);
        $max = max($max, 1);

        $rows = [];
        for ($star = 5; $star >= 1; $star--) {
            $total = (int) ($distribution[$star] ?? 0);
            $rows[] = [
                'label' => $star.'★',
                'total' => $total,
                'width' => ($total / $max) * 100,
            ];
        }

        return [
            'heading' => 'Distribution des notes',
            'description' => 'Satisfaction',
            'barColor' => 'k-fill-amber',
            'rows' => $rows,
            'empty' => 'Aucun avis publié.',
            'isEmpty' => array_sum(array_map('intval', $distribution)) === 0,
        ];
    }
}
