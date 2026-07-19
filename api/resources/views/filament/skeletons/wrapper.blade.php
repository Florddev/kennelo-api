<div
    class="k-skeleton"
    @if ($span) style="grid-column: {{ $span }}" @endif
    aria-hidden="true"
    aria-busy="true"
>
    <x-filament::section>
        @if ($heading)
            <x-slot name="heading">{{ $heading }}</x-slot>
        @endif
        @if ($description)
            <x-slot name="description">{{ $description }}</x-slot>
        @endif

        @include($view, $data)
    </x-filament::section>
</div>
