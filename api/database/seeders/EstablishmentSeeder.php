<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Establishment;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class EstablishmentSeeder extends Seeder
{
    private array $imageKeywords = [
        'kennel',
        'pet',
        'dog',
        'cattery',
        'animals',
    ];

    public function run(): void
    {
        $addresses = Address::inRandomOrder()->limit(5)->get();
        if ($addresses->isEmpty()) {
            throw new \RuntimeException('No addresses found. Run AddressSeeder first.');
        }

        $managers = User::inRandomOrder()->limit(5)->get();
        if ($managers->isEmpty()) {
            throw new \RuntimeException('No users found. Run UsersSeeder first.');
        }

        $collaborators = User::inRandomOrder()->limit(10)->get();

        $addresses->each(function ($address) use ($managers, $collaborators) {
            /** @var Establishment $establishment */
            $establishment = Establishment::factory()->create([
                'address_id' => $address->id,
                'manager_id' => $managers->random()->id,
            ]);

            if ($collaborators->isNotEmpty()) {
                $establishment->collaborators()->attach(
                    $collaborators->random(min(3, $collaborators->count()))->pluck('id')
                );
            }

            $keyword = $this->imageKeywords[array_rand($this->imageKeywords)];
            $this->seedEstablishmentImages($establishment, $keyword, rand(2, 4), true);
        });
    }

    private function seedEstablishmentImages(Establishment $establishment, string $keyword, int $count, bool $withAvatar): void
    {
        for ($i = 0; $i < $count; $i++) {
            try {
                $response = Http::withoutVerifying()->withOptions(['allow_redirects' => true])->timeout(15)->get("https://loremflickr.com/800/600/{$keyword}");

                if ($response->successful()) {
                    $tmpPath = tempnam(sys_get_temp_dir(), 'establishment_image_').'.jpg';
                    file_put_contents($tmpPath, $response->body());
                    $establishment->addMedia($tmpPath)->toMediaCollection(MediaService::COLLECTION_IMAGES);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if ($withAvatar) {
            try {
                $response = Http::withoutVerifying()->withOptions(['allow_redirects' => true])->timeout(15)->get("https://loremflickr.com/600/600/{$keyword}");

                if ($response->successful()) {
                    $tmpPath = tempnam(sys_get_temp_dir(), 'establishment_avatar_').'.jpg';
                    file_put_contents($tmpPath, $response->body());
                    $establishment->addMedia($tmpPath)->toMediaCollection(MediaService::COLLECTION_AVATAR);
                }
            } catch (\Throwable) {
            }
        }
    }
}
