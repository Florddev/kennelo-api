<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Base data
            RoleSeeder::class,
            AddressSeeder::class,
            UsersSeeder::class,
            EstablishmentSeeder::class,

            // Animal system (EAV)
            AnimalTypeSeeder::class,
            AttributeSeeder::class,
            PetSeeder::class,

            // Establishment capacities & availabilities
            EstablishmentCapacitySeeder::class,
            EstablishmentAvailabilitySeeder::class,

            // Explore demo data (random hosts with varied profiles)
            ExploreSeeder::class,

            // Bookings
            BookingSeeder::class,

            // Messaging
            ConversationSeeder::class,

            // Reviews
            ReviewCriteriaSeeder::class,
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
