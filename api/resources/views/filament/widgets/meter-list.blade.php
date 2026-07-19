<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ $heading }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>

        @if ($isEmpty)
            <p class="k-empty">{{ $empty }}</p>
        @else
            <div class="k-stack">
                @foreach ($rows as $row)
                    <div class="k-meter-row">
                        <span class="k-meter-label">{{ $row['label'] }}</span>
                        <div class="k-meter">
                            <div class="k-meter-fill {{ $barColor }}" style="width: {{ $row['width'] }}%"></div>
                        </div>
                        <span class="k-meter-value">{{ number_format($row['total'], 0, ',', ' ') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
