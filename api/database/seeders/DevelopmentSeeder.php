<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AddressSeeder::class,
            UsersSeeder::class,
            ScannerSeeder::class,
            ActivitySeeder::class,
            PetSeeder::class,
            ActivityCycleSeeder::class,
            ActivityAvailabilitySeeder::class,
            BookingSeeder::class,
            ExploreSeeder::class,
            ConversationSeeder::class,
            PetReviewSeeder::class,
        ]);

        User::factory(5)->create()->each(function ($user) {
            if (Role::where('name', 'user')->exists()) {
                $user->assignRole('user');
            }
        });

        User::factory(5)->unverified()->create()->each(function ($user) {
            if (Role::where('name', 'user')->exists()) {
                $user->assignRole('user');
            }
        });
    }
}
