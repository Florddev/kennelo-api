<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Entonnoir de prospection</x-slot>
        <x-slot name="description">Du prospect identifié à l'inscription</x-slot>

        <div class="k-stack">
            @foreach ($steps as $step)
                <div>
                    <div class="k-funnel-head">
                        <span class="k-name">{{ $step['label'] }}</span>
                        <span class="k-nums">
                            {{ number_format($step['value'], 0, ',', ' ') }}
                            @if ($step['rate'])
                                <span class="k-muted k-xs">· {{ $step['rate'] }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="k-meter k-lg">
                        <div class="k-meter-fill {{ $step['color'] }}" style="width: {{ $step['width'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
