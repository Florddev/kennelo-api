<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSkeleton;
use App\Services\Admin\Stats\StatsService;
use Filament\Widgets\Widget;
use Livewire\Attributes\Lazy;

#[Lazy(isolate: false)]
class BookingStatusBreakdownWidget extends Widget
{
    use HasSkeleton;

    protected string $view = 'filament.widgets.booking-status-breakdown';

    protected int|string|array $columnSpan = 1;

    protected function skeletonData(): array
    {
        return ['rows' => 5, 'variant' => 'row'];
    }

    protected function skeletonHeading(): ?string
    {
        return 'Réservations par statut';
    }

    protected function skeletonDescription(): ?string
    {
        return 'Répartition';
    }

    private const LABELS = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmées',
        'in_progress' => 'En cours',
        'completed' => 'Terminées',
        'cancelled' => 'Annulées',
        'rejected' => 'Refusées',
        'expired' => 'Expirées',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $byStatus = app(StatsService::class)->bookings()['by_status'];

        $rows = [];
        foreach (self::LABELS as $key => $label) {
            $total = $byStatus[$key] ?? 0;
            if ($total > 0) {
                $rows[] = ['label' => $label, 'total' => $total];
            }
        }

        return ['rows' => $rows];
    }
}
