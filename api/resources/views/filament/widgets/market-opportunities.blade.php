<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Départements à fort potentiel</x-slot>
        <x-slot name="description">Forte demande utilisateurs, peu de professionnels inscrits : zones à démarcher en priorité.</x-slot>

        @if (empty($opportunities))
            <p class="k-empty">Pas encore assez de données de recherche pour identifier des opportunités.</p>
        @else
            <div class="k-table-wrap">
                <table class="k-table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th class="k-num">Recherches</th>
                            <th class="k-num">Pros inscrits</th>
                            <th class="k-num">Potentiel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($opportunities as $row)
                            <tr>
                                <td class="k-strong">{{ $row['department'] }}</td>
                                <td class="k-num">{{ number_format($row['searches'], 0, ',', ' ') }}</td>
                                <td class="k-num">{{ $row['professionals'] }}</td>
                                <td class="k-num">
                                    @if ($row['professionals'] === 0)
                                        <x-filament::badge color="danger" class="inline-flex">Vierge</x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning" class="inline-flex">À renforcer</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
