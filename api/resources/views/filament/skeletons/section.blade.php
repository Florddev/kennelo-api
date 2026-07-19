@props(['rows' => 4, 'variant' => 'meter'])

<div class="k-stack">
    @for ($i = 0; $i < $rows; $i++)
        @if ($variant === 'meter')
            <div class="k-meter-row">
                <div class="k-skel-bar" style="width: 5rem"></div>
                <div class="k-meter"></div>
                <div class="k-skel-bar" style="width: 2rem"></div>
            </div>
        @else
            <div class="k-meter-row" style="justify-content: space-between;">
                <div class="k-skel-bar" style="width: 33%"></div>
                <div class="k-skel-bar" style="width: 2.5rem"></div>
            </div>
        @endif
    @endfor
</div>
