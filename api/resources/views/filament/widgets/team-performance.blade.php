<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Performance par membre</x-slot>
        <x-slot name="description">Équipe commerciale</x-slot>

        @if (empty($members))
            <p class="k-empty">Aucune activité de prospection assignée pour l'instant.</p>
        @else
            <div class="k-table-wrap">
                <table class="k-table">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th class="k-num">Contacts</th>
                            <th class="k-num">Conversions</th>
                            <th class="k-num">Imports</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $row)
                            <tr>
                                <td class="k-strong">{{ $row['member'] }}</td>
                                <td class="k-num">{{ $row['contacts'] }}</td>
                                <td class="k-num">{{ $row['conversions'] }}</td>
                                <td class="k-num">{{ $row['imported'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
