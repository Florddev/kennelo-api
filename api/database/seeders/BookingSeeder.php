<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    private const WALK_PRICE = 15.00;

    private array $petPrices = [
        'Rex' => 30.00,
        'Max' => 30.00,
        'Minou' => 25.00,
        'Kiwi' => 20.00,
    ];

    private array $dogPets = ['Rex', 'Max'];

    private array $templates = [
        ['status' => 'completed', 'startOffset' => -45, 'nights' => 5, 'pets' => ['Rex'], 'requests' => 'Rex adore jouer avec la balle.'],
        ['status' => 'completed', 'startOffset' => -30, 'nights' => 3, 'pets' => ['Minou'], 'requests' => 'Minou est très calme et reste cachée la journée.'],
        ['status' => 'completed', 'startOffset' => -20, 'nights' => 7, 'pets' => ['Rex', 'Minou'], 'requests' => 'Prévoir des espaces séparés pour les repas.'],
        ['status' => 'completed', 'startOffset' => -14, 'nights' => 4, 'pets' => ['Max'], 'requests' => 'Max prend ses médicaments matin et soir.'],

        ['status' => 'in_progress', 'startOffset' => -2, 'nights' => 6, 'pets' => ['Rex'], 'requests' => 'Séjour en cours, Rex sort deux fois par jour.'],
        ['status' => 'in_progress', 'startOffset' => -1, 'nights' => 5, 'pets' => ['Max'], 'requests' => 'Max prend ses médicaments matin et soir.'],
        ['status' => 'in_progress', 'startOffset' => -3, 'nights' => 8, 'pets' => ['Minou', 'Kiwi'], 'requests' => 'Kiwi a besoin de lumière naturelle.'],

        ['status' => 'confirmed', 'startOffset' => 4, 'nights' => 5, 'pets' => ['Max'], 'requests' => null],
        ['status' => 'confirmed', 'startOffset' => 12, 'nights' => 7, 'pets' => ['Rex', 'Kiwi'], 'requests' => null],
        ['status' => 'pending', 'startOffset' => 20, 'nights' => 6, 'pets' => ['Kiwi'], 'requests' => 'Kiwi aime chanter le matin !'],
        ['status' => 'cancelled', 'startOffset' => 8, 'nights' => 3, 'pets' => ['Rex'], 'requests' => null],
    ];

    public function run(): void
    {
        $userId = User::where('email', 'user@orus.com')->value('id');
        if (! $userId) {
            throw new \RuntimeException('User with email user@orus.com not found. Run UsersSeeder first.');
        }

        $managerId = User::where('email', 'manager@orus.com')->value('id');
        if (! $managerId) {
            throw new \RuntimeException('User with email manager@orus.com not found. Run UsersSeeder first.');
        }

        $activities = Activity::where('manager_id', $managerId)
            ->orderBy('created_at')
            ->get();
        if ($activities->isEmpty()) {
            throw new \RuntimeException('No activity found for manager@orus.com. Run ActivitySeeder first.');
        }

        $pets = Pet::whereIn('name', ['Rex', 'Minou', 'Kiwi', 'Max'])->pluck('id', 'name')->toArray();
        if (count($pets) !== 4) {
            throw new \RuntimeException('Missing pets. Run PetSeeder first.');
        }

        $dogTypeId = AnimalType::where('code', 'dog')->value('id');
        if (! $dogTypeId) {
            throw new \RuntimeException('Missing dog animal type. Run AnimalTypeSeeder first.');
        }

        DB::transaction(function () use ($activities, $dogTypeId, $userId, $pets): void {
            foreach ($activities as $activity) {
                $walkService = Service::firstOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'animal_type_id' => $dogTypeId,
                        'name' => 'Promenade quotidienne',
                    ],
                    [
                        'description' => 'Promenade d\'une heure dans le parc',
                        'is_included' => false,
                        'price' => self::WALK_PRICE,
                    ]
                );

                foreach ($this->templates as $template) {
                    $this->createBooking($userId, $activity->id, $walkService->id, $pets, $template);
                }
            }
        });
    }

    private function createBooking(string $userId, string $activityId, string $walkServiceId, array $pets, array $template): void
    {
        $checkIn = now()->addDays($template['startOffset'])->startOfDay();
        $checkOut = $checkIn->copy()->addDays($template['nights']);

        $createdAt = $checkIn->copy()->subDays(7);
        $updatedAt = match ($template['status']) {
            'completed' => $checkOut->copy(),
            'in_progress' => $checkIn->copy(),
            default => $createdAt->copy(),
        };

        $petsSubtotal = 0.0;
        $hasDog = false;
        $bookingPetsRows = [];

        foreach ($template['pets'] as $petName) {
            $price = $this->petPrices[$petName];
            $subtotal = $price * $template['nights'];
            $petsSubtotal += $subtotal;

            if (in_array($petName, $this->dogPets, true)) {
                $hasDog = true;
            }

            $bookingPetsRows[] = [
                'pet_id' => $pets[$petName],
                'price_per_night' => $price,
                'number_of_nights' => $template['nights'],
                'subtotal' => $subtotal,
            ];
        }

        $servicesSubtotal = 0.0;
        if ($hasDog) {
            $servicesSubtotal = self::WALK_PRICE * $template['nights'];
        }

        $bookingId = (string) Str::uuid();
        DB::table('bookings')->insert([
            'id' => $bookingId,
            'user_id' => $userId,
            'activity_id' => $activityId,
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkOut->format('Y-m-d'),
            'total_price' => $petsSubtotal + $servicesSubtotal,
            'status' => $template['status'],
            'special_requests' => $template['requests'],
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        DB::table('booking_pets')->insert(array_map(fn (array $row): array => [
            'booking_id' => $bookingId,
            'pet_id' => $row['pet_id'],
            'price_per_night' => $row['price_per_night'],
            'number_of_nights' => $row['number_of_nights'],
            'subtotal' => $row['subtotal'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ], $bookingPetsRows));

        if ($hasDog) {
            DB::table('booking_services')->insert([
                'booking_id' => $bookingId,
                'service_id' => $walkServiceId,
                'quantity' => $template['nights'],
                'unit_price' => self::WALK_PRICE,
                'subtotal' => $servicesSubtotal,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
