<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\EstablishmentType;
use App\Enums\ReviewerType;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use App\Models\Review;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

class ExploreSeeder extends Seeder
{
    private array $pricingByCode = [
        'dog' => [20, 45],
        'cat' => [15, 30],
        'rabbit' => [10, 20],
        'bird' => [8, 18],
        'hamster' => [6, 12],
        'guinea_pig' => [6, 14],
        'ferret' => [12, 22],
        'reptile' => [12, 25],
    ];

    private array $imageKeywords = ['kennel', 'pet', 'dog', 'cattery', 'animals'];

    private array $proTypes = [
        EstablishmentType::BOARDING,
        EstablishmentType::BREEDING,
        EstablishmentType::DAYCARE,
        EstablishmentType::SHELTER,
    ];

    private array $individualTypes = [
        EstablishmentType::PET_SITTER,
        EstablishmentType::HOME_CARE,
        EstablishmentType::HOST_FAMILY,
        EstablishmentType::MOBILE_BOARDING,
    ];

    public function run(): void
    {
        $animalTypes = AnimalType::all()->keyBy('code');
        $userRole = Role::where('name', 'user')->first();

        $userCount = random_int(10, 15);

        for ($i = 0; $i < $userCount; $i++) {
            $isProHost = $i % 3 !== 0;

            $user = User::factory()->create();

            if ($userRole) {
                $user->assignRole($userRole);
            }

            $estabCount = random_int(1, 2);

            for ($j = 0; $j < $estabCount; $j++) {
                $type = fake()->randomElement($isProHost ? $this->proTypes : $this->individualTypes);

                $establishment = Establishment::factory()->create([
                    'manager_id' => $user->id,
                    'siret' => $isProHost ? fake()->numerify('##############') : null,
                    'type' => $type,
                    'is_active' => true,
                ]);

                $this->seedCapacities($establishment, $animalTypes);
                $this->seedImages($establishment);
            }
        }

        $this->seedReviews($userRole);
    }

    private function seedReviews(?Role $userRole): void
    {
        $establishments = Establishment::where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $reviewers = User::factory(10)->create()->each(function (User $user) use ($userRole): void {
            if ($userRole) {
                $user->assignRole($userRole);
            }
        });

        foreach ($establishments as $establishment) {
            $count = random_int(3, 8);

            for ($i = 0; $i < $count; $i++) {
                $reviewer = $reviewers->random();
                $daysAgo = random_int(30, 365);

                $booking = Booking::create([
                    'user_id' => $reviewer->id,
                    'establishment_id' => $establishment->id,
                    'check_in_date' => Carbon::now()->subDays($daysAgo + 7)->format('Y-m-d'),
                    'check_out_date' => Carbon::now()->subDays($daysAgo)->format('Y-m-d'),
                    'total_price' => random_int(50, 300),
                    'status' => BookingStatus::COMPLETED,
                ]);

                Review::create([
                    'booking_id' => $booking->id,
                    'reviewer_id' => $reviewer->id,
                    'reviewer_type' => ReviewerType::USER,
                    'overall_rating' => fake()->randomFloat(1, 3.0, 5.0),
                    'comment' => fake()->boolean(70) ? fake()->paragraph() : null,
                    'would_recommend' => fake()->boolean(85),
                    'is_published' => true,
                    'published_at' => Carbon::now()->subDays($daysAgo - 1),
                ]);
            }
        }
    }

    private function seedCapacities(Establishment $establishment, Collection $animalTypes): void
    {
        if ($animalTypes->isEmpty()) {
            return;
        }

        $codes = collect($this->pricingByCode)->keys()
            ->filter(fn ($code) => $animalTypes->has($code))
            ->shuffle();

        $count = random_int(2, min(4, $codes->count()));

        foreach ($codes->take($count) as $code) {
            [$min, $max] = $this->pricingByCode[$code];
            $animalType = $animalTypes->get($code);

            if (! $animalType instanceof AnimalType) {
                continue;
            }

            EstablishmentCapacity::create([
                'establishment_id' => $establishment->id,
                'animal_type_id' => $animalType->getKey(),
                'max_capacity' => random_int(2, 10),
                'price_per_night' => random_int($min * 100, $max * 100) / 100,
            ]);
        }
    }

    private function seedImages(Establishment $establishment): void
    {
        $keyword = $this->imageKeywords[array_rand($this->imageKeywords)];

        try {
            $response = Http::withoutVerifying()
                ->withOptions(['allow_redirects' => true])
                ->timeout(15)
                ->get("https://loremflickr.com/600/600/{$keyword}");

            if ($response->successful()) {
                $tmp = tempnam(sys_get_temp_dir(), 'explore_avatar_').'.jpg';
                file_put_contents($tmp, $response->body());
                $establishment->addMedia($tmp)->toMediaCollection(MediaService::COLLECTION_AVATAR);
            }
        } catch (\Throwable) {
        }

        $imageCount = random_int(1, 3);
        for ($k = 0; $k < $imageCount; $k++) {
            try {
                $response = Http::withoutVerifying()
                    ->withOptions(['allow_redirects' => true])
                    ->timeout(15)
                    ->get("https://loremflickr.com/800/600/{$keyword}");

                if ($response->successful()) {
                    $tmp = tempnam(sys_get_temp_dir(), 'explore_img_').'.jpg';
                    file_put_contents($tmp, $response->body());
                    $establishment->addMedia($tmp)->toMediaCollection(MediaService::COLLECTION_IMAGES);
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }
}
