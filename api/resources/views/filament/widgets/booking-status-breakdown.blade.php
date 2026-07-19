<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Réservations par statut</x-slot>
        <x-slot name="description">Répartition</x-slot>

        @if (empty($rows))
            <p class="k-empty">Aucune réservation.</p>
        @else
            <div>
                @foreach ($rows as $row)
                    <div class="k-row">
                        <span>{{ $row['label'] }}</span>
                        <span class="k-row-value">{{ number_format($row['total'], 0, ',', ' ') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
