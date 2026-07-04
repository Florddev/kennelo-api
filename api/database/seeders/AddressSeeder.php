<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $addresses = [
            [
                'line1' => '15 Rue de la Paix',
                'line2' => null,
                'postal_code' => '75001',
                'city' => 'Paris',
                'region' => 'Île-de-France',
                'country' => 'FR',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
            [
                'line1' => '23 Avenue des Champs',
                'line2' => 'Bâtiment A',
                'postal_code' => '69001',
                'city' => 'Lyon',
                'region' => 'Auvergne-Rhône-Alpes',
                'country' => 'FR',
                'latitude' => 45.7640,
                'longitude' => 4.8357,
            ],
            [
                'line1' => '8 Boulevard de la Liberté',
                'line2' => null,
                'postal_code' => '13001',
                'city' => 'Marseille',
                'region' => 'Provence-Alpes-Côte d\'Azur',
                'country' => 'FR',
                'latitude' => 43.2965,
                'longitude' => 5.3698,
            ],
            [
                'line1' => '42 Rue du Commerce',
                'line2' => null,
                'postal_code' => '44000',
                'city' => 'Nantes',
                'region' => 'Pays de la Loire',
                'country' => 'FR',
                'latitude' => 47.2184,
                'longitude' => -1.5536,
            ],
            [
                'line1' => '12 Place de la République',
                'line2' => null,
                'postal_code' => '33000',
                'city' => 'Bordeaux',
                'region' => 'Nouvelle-Aquitaine',
                'country' => 'FR',
                'latitude' => 44.8378,
                'longitude' => -0.5792,
            ],
        ];

        DB::transaction(function () use ($addresses): void {
            foreach ($addresses as $addressData) {
                Address::firstOrCreate(
                    ['line1' => $addressData['line1'], 'postal_code' => $addressData['postal_code']],
                    $addressData
                );
            }
        });
    }
}
