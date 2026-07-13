<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ExploreSeeder extends Seeder
{
    private array $pricingByCode = [
        'dog' => [20, 45],
        'cat' => [15, 30],
        'rabbit' => [10, 20],
        'bird' => [8, 18],
        'rodent' => [6, 12],
        'ferret' => [12, 22],
        'reptile' => [12, 25],
    ];

    private array $imageKeywords = ['kennel', 'pet', 'dog', 'cattery', 'animals'];

    private array $imagePool = ['avatar' => [], 'gallery' => []];

    private array $proTypes = [
        ActivityTypeEnum::BOARDING,
        ActivityTypeEnum::BREEDING,
        ActivityTypeEnum::DAYCARE,
        ActivityTypeEnum::SHELTER,
    ];

    private array $individualTypes = [
        ActivityTypeEnum::PET_SITTER,
        ActivityTypeEnum::HOME_CARE,
        ActivityTypeEnum::HOST_FAMILY,
        ActivityTypeEnum::MOBILE_BOARDING,
    ];

    public function run(): void
    {
        $this->buildImagePool();

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

                $activity = Activity::factory()->create([
                    'manager_id' => $user->id,
                    'siret' => $isProHost ? fake()->numerify('##############') : null,
                    'type' => $type,
                    'is_active' => true,
                ]);

                $this->seedCapacities($activity, $animalTypes);
                $this->seedImages($activity);
            }
        }

        $this->seedReviews($userRole);
    }

    private function buildImagePool(): void
    {
        foreach ($this->imageKeywords as $keyword) {
            $avatar = $this->fetchImage(600, 600, $keyword);
            if ($avatar !== null) {
                $this->imagePool['avatar'][] = $avatar;
            }

            $gallery = $this->fetchImage(800, 600, $keyword);
            if ($gallery !== null) {
                $this->imagePool['gallery'][] = $gallery;
            }
        }
    }

    private function seedReviews(?Role $userRole): void
    {
        $activities = Activity::where('is_active', true)->get();

        $reviewers = User::factory(10)->create()->each(function (User $user) use ($userRole): void {
            if ($userRole) {
                $user->assignRole($userRole);
            }
        });

        foreach ($activities as $activity) {
            $count = random_int(3, 8);

            for ($i = 0; $i < $count; $i++) {
                $reviewer = $reviewers->random();
                $daysAgo = random_int(30, 365);

                $booking = Booking::create([
                    'user_id' => $reviewer->id,
                    'activity_id' => $activity->id,
                    'check_in_date' => Carbon::now()->subDays($daysAgo + 7)->format('Y-m-d'),
                    'check_out_date' => Carbon::now()->subDays($daysAgo)->format('Y-m-d'),
                    'total_price' => random_int(50, 300),
                    'status' => BookingStatusEnum::COMPLETED,
                ]);

                Review::create([
                    'booking_id' => $booking->id,
                    'reviewer_id' => $reviewer->id,
                    'reviewer_type' => ReviewerTypeEnum::USER,
                    'overall_rating' => fake()->randomFloat(1, 3.0, 5.0),
                    'comment' => fake()->boolean(70) ? fake()->paragraph() : null,
                    'would_recommend' => fake()->boolean(85),
                    'is_published' => true,
                    'published_at' => Carbon::now()->subDays($daysAgo - 1),
                ]);
            }
        }
    }

    private function seedCapacities(Activity $activity, Collection $animalTypes): void
    {
        if ($animalTypes->isEmpty()) {
            return;
        }

        $codes = collect($this->pricingByCode)->keys()
            ->filter(fn ($code) => $animalTypes->has($code))
            ->shuffle();

        $count = random_int(2, min(4, $codes->count()));

        $cycle = ActivityCycle::create([
            'activity_id' => $activity->id,
            'start_date' => null,
            'end_date' => null,
            'priority' => 0,
            'is_active' => true,
        ]);

        foreach ($codes->take($count) as $code) {
            [$min, $max] = $this->pricingByCode[$code];

            $setting = ActivityCycleSetting::create([
                'activity_cycle_id' => $cycle->id,
                'animal_type_id' => $animalTypes->get($code)->getKey(),
                'max_capacity' => random_int(2, 10),
            ]);

            $price = random_int($min * 100, $max * 100) / 100;

            $now = Carbon::now();
            $priceRows = array_map(fn (int $weekday): array => [
                'id' => (string) Str::uuid(),
                'activity_cycle_setting_id' => $setting->id,
                'weekday' => $weekday,
                'price' => $price,
                'created_at' => $now,
                'updated_at' => $now,
            ], WeekDayEnum::values());

            $setting->prices()->insert($priceRows);
        }
    }

    private function seedImages(Activity $activity): void
    {
        if (! empty($this->imagePool['avatar'])) {
            $content = $this->imagePool['avatar'][array_rand($this->imagePool['avatar'])];
            $tmp = tempnam(sys_get_temp_dir(), 'explore_avatar_').'.jpg';
            file_put_contents($tmp, $content);
            $activity->addMedia($tmp)->toMediaCollection(MediaService::COLLECTION_AVATAR);
        }

        $imageCount = random_int(1, 3);
        for ($k = 0; $k < $imageCount; $k++) {
            if (empty($this->imagePool['gallery'])) {
                break;
            }
            $content = $this->imagePool['gallery'][array_rand($this->imagePool['gallery'])];
            $tmp = tempnam(sys_get_temp_dir(), 'explore_img_').'.jpg';
            file_put_contents($tmp, $content);
            $activity->addMedia($tmp)->toMediaCollection(MediaService::COLLECTION_IMAGES);
        }
    }

    private function fetchImage(int $width, int $height, string $keyword): ?string
    {
        if (! config('seeding.remote_images')) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->withOptions(['allow_redirects' => true])
                ->timeout(8)
                ->get("https://loremflickr.com/{$width}/{$height}/{$keyword}");

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
