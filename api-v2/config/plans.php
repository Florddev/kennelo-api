<?php

declare(strict_types=1);

// Quotas : max_activities, max_periods et max_members par entreprise ; max_photos par activité. -1 = illimité.
return [
    'free' => [
        'name' => 'Gratuit',
        'commission_rate' => env('PLAN_FREE_COMMISSION_RATE', '0.08'),
        'stripe_price_id' => null,
        'limits' => [
            'max_activities' => (int) env('PLAN_FREE_MAX_ACTIVITIES', 1),
            'max_periods' => (int) env('PLAN_FREE_MAX_PERIODS', 3),
            'max_members' => (int) env('PLAN_FREE_MAX_MEMBERS', 1),
            'max_photos' => (int) env('PLAN_FREE_MAX_PHOTOS', 5),
        ],
    ],

    'starter' => [
        'name' => 'Starter',
        'commission_rate' => env('PLAN_STARTER_COMMISSION_RATE', '0.03'),
        'price_monthly' => env('PLAN_STARTER_PRICE_MONTHLY', '15.00'),
        'stripe_price_id' => env('STRIPE_PRICE_STARTER') ?: null,
        'limits' => [
            'max_activities' => (int) env('PLAN_STARTER_MAX_ACTIVITIES', 3),
            'max_periods' => (int) env('PLAN_STARTER_MAX_PERIODS', 10),
            'max_members' => (int) env('PLAN_STARTER_MAX_MEMBERS', 5),
            'max_photos' => (int) env('PLAN_STARTER_MAX_PHOTOS', 15),
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'commission_rate' => env('PLAN_PRO_COMMISSION_RATE', '0.00'),
        'price_monthly' => env('PLAN_PRO_PRICE_MONTHLY', '59.00'),
        'stripe_price_id' => env('STRIPE_PRICE_PRO') ?: null,
        'limits' => [
            'max_activities' => (int) env('PLAN_PRO_MAX_ACTIVITIES', -1),
            'max_periods' => (int) env('PLAN_PRO_MAX_PERIODS', -1),
            'max_members' => (int) env('PLAN_PRO_MAX_MEMBERS', -1),
            'max_photos' => (int) env('PLAN_PRO_MAX_PHOTOS', -1),
        ],
    ],

    'downgrade' => [
        'soft_disable' => [
            'activities' => (bool) env('PLAN_DOWNGRADE_SOFT_DISABLE_ACTIVITIES', false),
            'periods' => (bool) env('PLAN_DOWNGRADE_SOFT_DISABLE_PERIODS', false),
            'photos' => (bool) env('PLAN_DOWNGRADE_SOFT_DISABLE_PHOTOS', false),
        ],
    ],
];
