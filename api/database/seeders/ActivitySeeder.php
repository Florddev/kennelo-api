<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Address;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ActivitySeeder extends Seeder
{
    private const ACTIVITY_COUNT = 4;

    private array $imageKeywords = [
        'kennel',
        'pet',
        'dog',
        'cattery',
        'animals',
    ];

    public function run(): void
    {
        $manager = User::where('email', 'manager@orus.com')->first();
        if (! $manager) {
            throw new \RuntimeException('Manager manager@orus.com not found. Run UsersSeeder first.');
        }

        $addresses = Address::inRandomOrder()->limit(self::ACTIVITY_COUNT)->get();
        if ($addresses->count() < self::ACTIVITY_COUNT) {
            throw new \RuntimeException('Not enough addresses. Run AddressSeeder first.');
        }

        $collaborators = User::where('id', '!=', $manager->id)->get();

        $addresses->each(function ($address) use ($manager, $collaborators) {
            /** @var Activity $activity */
            $activity = Activity::factory()->create([
                'address_id' => $address->id,
                'manager_id' => $manager->id,
            ]);

            if ($collaborators->isNotEmpty()) {
                $activity->collaborators()->attach(
                    $collaborators->random(min(2, $collaborators->count()))->pluck('id')
                );
            }

            $keyword = $this->imageKeywords[array_rand($this->imageKeywords)];
            $this->seedActivityImages($activity, $keyword, rand(2, 4), true);
        });
    }

    private function seedActivityImages(Activity $activity, string $keyword, int $count, bool $withAvatar): void
    {
        for ($i = 0; $i < $count; $i++) {
            $body = $this->fetchRemoteImage("https://loremflickr.com/800/600/{$keyword}")
                ?? $this->placeholderImage(800, 600, $activity->name);

            $activity->addMediaFromString($body)
                ->usingFileName('activity_'.Str::uuid()->toString().'.jpg')
                ->toMediaCollection(MediaService::COLLECTION_IMAGES);
        }

        if ($withAvatar) {
            $body = $this->fetchRemoteImage("https://loremflickr.com/600/600/{$keyword}")
                ?? $this->placeholderImage(600, 600, $activity->name);

            $activity->addMediaFromString($body)
                ->usingFileName('avatar_'.Str::uuid()->toString().'.jpg')
                ->toMediaCollection(MediaService::COLLECTION_AVATAR);
        }
    }

    private function fetchRemoteImage(string $url): ?string
    {
        try {
            $response = Http::withoutVerifying()->withOptions(['allow_redirects' => true])->timeout(8)->get($url);

            if ($response->successful() && $response->body() !== '') {
                return $response->body();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function placeholderImage(int $width, int $height, string $label): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, random_int(40, 120), random_int(80, 160), random_int(120, 200));
        imagefill($image, 0, 0, $background);

        $textColor = imagecolorallocate($image, 255, 255, 255);
        $text = strtoupper(substr(trim($label), 0, 2));
        imagestring($image, 5, (int) ($width / 2) - 10, (int) ($height / 2) - 8, $text, $textColor);

        ob_start();
        imagejpeg($image, null, 80);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }
}
