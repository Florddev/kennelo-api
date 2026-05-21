<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
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
                $establishment = Establishment::factory()->create([
                    'manager_id' => $user->id,
                    'siret' => $isProHost ? fake()->numerify('##############') : null,
                    'is_active' => true,
                ]);

                $this->seedCapacities($establishment, $animalTypes);
                $this->seedImages($establishment);
            }
        }
    }

    private function seedCapacities(Establishment $establishment, $animalTypes): void
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

            EstablishmentCapacity::create([
                'establishment_id' => $establishment->id,
                'animal_type_id' => $animalTypes->get($code)->id,
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
