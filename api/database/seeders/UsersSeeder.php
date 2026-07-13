<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User
        User::firstOrCreate(
            ['email' => 'user@orus.com'],
            [
                'first_name' => 'User',
                'last_name' => 'Account',
                'password' => Hash::make('user'),
                'email_verified_at' => now(),
                'is_id_verified' => false,
            ]
        )->assignRole('user');

        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'manager@orus.com'],
            [
                'first_name' => 'Manager',
                'last_name' => 'Account',
                'password' => Hash::make('manager'),
                'email_verified_at' => now(),
                'is_id_verified' => false,
            ]
        );
        $manager->assignRole('manager');

        $proPlan = SubscriptionPlan::where('slug', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::firstOrCreate(
                ['user_id' => $manager->id],
                [
                    'subscription_plan_id' => $proPlan->id,
                    'stripe_subscription_id' => 'seed_manager_sub',
                    'stripe_customer_id' => 'seed_manager_cus',
                    'status' => SubscriptionStatusEnum::ACTIVE,
                    'current_period_start' => now(),
                    'current_period_end' => now()->addYear(),
                ]
            );
        }

        // Admin
        User::firstOrCreate(
            ['email' => 'admin@orus.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Account',
                'password' => Hash::make('admin'),
                'email_verified_at' => now(),
                'is_id_verified' => false,
            ]
        )->assignRole('admin');
    }
}
