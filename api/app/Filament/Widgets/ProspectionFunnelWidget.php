<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\Widget;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class ProspectionFunnelWidget extends Widget
{
    use HasSkeleton;

    protected string $view = 'filament.widgets.prospection-funnel';

    protected int|string|array $columnSpan = 1;

    protected function skeletonData(): array
    {
        return ['rows' => 3, 'variant' => 'meter'];
    }

    protected function skeletonHeading(): ?string
    {
        return 'Entonnoir de prospection';
    }

    protected function skeletonDescription(): ?string
    {
        return 'Du prospect identifié à l\'inscription';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $funnel = app(StatsService::class)->business()['prospection_funnel'];
        $max = max($funnel['identified'], 1);

        return [
            'steps' => [
                [
                    'label' => 'Identifiés',
                    'value' => $funnel['identified'],
                    'color' => 'k-fill-blue',
                    'width' => max(($funnel['identified'] / $max) * 100, 2),
                    'rate' => null,
                ],
                [
                    'label' => 'Contactés',
                    'value' => $funnel['contacted'],
                    'color' => 'k-fill-amber',
                    'width' => max(($funnel['contacted'] / $max) * 100, 2),
                    'rate' => $funnel['contact_rate'].'% prise de contact',
                ],
                [
                    'label' => 'Inscrits',
                    'value' => $funnel['registered'],
                    'color' => 'k-fill-green',
                    'width' => max(($funnel['registered'] / $max) * 100, 2),
                    'rate' => $funnel['conversion_rate'].'% conversion',
                ],
            ],
        ];
    }
}
