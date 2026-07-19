@props(['type' => 'bar'])

@if ($type === 'doughnut')
    <div class="k-skel-circle"></div>
@else
    <div class="k-skel-chart">
        @foreach ([55, 80, 40, 95, 65, 70, 45, 85, 60, 50, 75, 90] as $h)
            <div class="k-skel-chart-bar" style="height: {{ $h }}%"></div>
        @endforeach
    </div>
@endif
