<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

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
      
        if (app()->environment(['local', 'development', 'staging'])) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
