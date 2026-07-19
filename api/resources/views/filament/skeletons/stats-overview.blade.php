@props(['count' => 4])

<div class="k-grid k-grid-{{ min($count, 4) }}">
    @for ($i = 0; $i < $count; $i++)
        <div class="fi-wi-stats-overview-stat">
            <div class="k-stack-sm" style="padding: 0.25rem 0;">
                <div class="k-skel-bar k-sm" style="width: 60%"></div>
                <div class="k-skel-bar k-lg" style="width: 45%"></div>
                <div class="k-skel-bar k-sm" style="width: 75%"></div>
            </div>
        </div>
    @endfor
</div>
